<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * 予約申請フォームの表示
     * フォームを開いた時点で selecting レコードを作成して一時ロックする
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'room_id'   => 'required|exists:rooms,id',
            'date'      => 'required|date|after_or_equal:today',

            // 通常導線は period、確認画面からの修正導線は periods[] を許容する
            'period'    => 'nullable|integer|min:1|max:6|required_without:periods',
            'periods'   => 'nullable|array|min:1|max:2',
            'periods.*' => 'integer|min:1|max:6',
        ]);

        $selectedRoom = Room::find($validated['room_id']);

        if (!$selectedRoom) {
            return redirect()->route('user.home.index')->with('error', '教室がみつかりません。');
        }

        $date = $validated['date'];
        $room = $selectedRoom;

        // period が無い場合は periods[] の先頭を代表時限として扱う
        $period = $validated['period'] ?? null;

        if (!$period && !empty($validated['periods'])) {
            $periods = array_map('intval', $validated['periods']);
            sort($periods);
            $period = $periods[0];
        }

        $period = (int) $period;

        // 自分の既存 selecting のみ削除する
        // ※期限切れ selecting の全件削除は create() ごとには実行しない
        Reservation::where('user_id', Auth::id())
            ->where('status', 'selecting')
            ->delete();

        $result = DB::transaction(function () use ($room, $date, $period) {
            $alreadyTaken = Reservation::where('room_id', $room->id)
                ->where('date', $date)
                ->where('period', $period)
                ->where('user_id', '!=', Auth::id())
                ->where(function ($query) {
                    $query->whereIn('status', ['pending', 'reserved', 'in_use'])
                        ->orWhere(function ($q) {
                            $q->where('status', 'selecting')
                                ->where('created_at', '>=', now()->subMinutes(10));
                        });
                })
                ->lockForUpdate()
                ->exists();

            $lessonExists = RoomLesson::where('room_id', $room->id)
                ->where('date', $date)
                ->where('period', $period)
                ->lockForUpdate()
                ->exists();

            if ($alreadyTaken || $lessonExists) {
                return false;
            }

            Reservation::create([
                'user_id' => Auth::id(),
                'room_id' => $room->id,
                'date'    => $date,
                'period'  => $period,
                'purpose' => '選択中',
                'status'  => 'selecting',
            ]);

            return true;
        });

        if (!$result) {
            return redirect()->route('user.home.index')
                ->with('error', 'その時間帯はすでに利用できません。');
        }

        $reservedPeriods = Reservation::where('room_id', $room->id)
            ->where('date', $date)
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'reserved', 'in_use'])
                    ->orWhere(function ($q) {
                        $q->where('status', 'selecting')
                            ->where('created_at', '>=', now()->subMinutes(10));
                    });
            })
            ->pluck('period')
            ->toArray();

        $lessonPeriods = RoomLesson::where('room_id', $room->id)
            ->where('date', $date)
            ->pluck('period')
            ->toArray();

        return view('user.reservation-form', compact('room', 'date', 'period', 'reservedPeriods', 'lessonPeriods'));
    }

    /**
     * 予約内容の重複チェックと確認画面表示
     */
    public function check(Request $request)
    {
        $validated = $request->validate([
            'room_id'    => 'required|exists:rooms,id',
            'date'       => 'required|date|after_or_equal:today',
            'periods'    => 'required|array|min:1|max:2',
            'periods.*'  => 'integer|min:1|max:6',
            'purpose'    => 'required|string|max:100',
            'members'    => 'nullable|string',
        ]);

        $selectedRoom = Room::find($validated['room_id']);

        if (!$selectedRoom) {
            return redirect()->route('user.home.index')->with('error', '教室がみつかりません。');
        }

        $existing = Reservation::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->whereIn('period', $validated['periods'])
            ->where('user_id', '!=', Auth::id())
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'reserved', 'in_use'])
                    ->orWhere(function ($q) {
                        $q->where('status', 'selecting')
                            ->where('created_at', '>=', now()->subMinutes(10));
                    });
            })
            ->exists();

        $existingLesson = RoomLesson::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->whereIn('period', $validated['periods'])
            ->exists();

        if ($existing || $existingLesson) {
            return back()
                ->withErrors(['periods' => '選択された時間帯の中に、すでに予約済みまたは授業が登録されている枠が含まれています。'])
                ->withInput();
        }

        $periods = array_map('intval', $validated['periods']);
        sort($periods);

        for ($i = 1; $i < count($periods); $i++) {
            if ($periods[$i] - $periods[$i - 1] !== 1) {
                return back()
                    ->withErrors(['periods' => '複数時限を予約する場合は、連続した時限のみ選択してください。'])
                    ->withInput();
            }
        }

        if (count($periods) > 1) {
            $existingSelectingPeriods = Reservation::where('user_id', Auth::id())
                ->where('status', 'selecting')
                ->where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('period', $periods)
                ->where('created_at', '>=', now()->subMinutes(10))
                ->pluck('period')
                ->map(fn ($period) => (int) $period)
                ->toArray();

            $missingPeriods = array_values(array_diff($periods, $existingSelectingPeriods));

            try {
                DB::transaction(function () use ($validated, $missingPeriods) {
                    foreach ($missingPeriods as $missingPeriod) {
                        $alreadyTaken = Reservation::where('room_id', $validated['room_id'])
                            ->where('date', $validated['date'])
                            ->where('period', $missingPeriod)
                            ->where('user_id', '!=', Auth::id())
                            ->where(function ($query) {
                                $query->whereIn('status', ['pending', 'reserved', 'in_use'])
                                    ->orWhere(function ($q) {
                                        $q->where('status', 'selecting')
                                            ->where('created_at', '>=', now()->subMinutes(10));
                                    });
                            })
                            ->lockForUpdate()
                            ->exists();

                        $lessonExists = RoomLesson::where('room_id', $validated['room_id'])
                            ->where('date', $validated['date'])
                            ->where('period', $missingPeriod)
                            ->lockForUpdate()
                            ->exists();

                        if ($alreadyTaken || $lessonExists) {
                            throw new \RuntimeException('Selected period is already taken.');
                        }

                        Reservation::create([
                            'user_id' => Auth::id(),
                            'room_id' => $validated['room_id'],
                            'date'    => $validated['date'],
                            'period'  => $missingPeriod,
                            'purpose' => '選択中',
                            'status'  => 'selecting',
                        ]);
                    }
                });
            } catch (\RuntimeException $e) {
                return back()
                    ->withErrors(['periods' => 'タッチの差で選択した時間帯が埋まってしまいました。もう一度選び直してください。'])
                    ->withInput();
            }
        }

        $reservationData = [
            'room_id'        => $validated['room_id'],
            'room_name'      => $selectedRoom->name,
            'date'           => $validated['date'],
            'periods'        => $periods,
            'period_display' => implode(', ', $periods),
            'purpose'        => $validated['purpose'],
            'members'        => $validated['members'],
            'user_name'      => Auth::user()->name,
            'student_id'     => Auth::user()->student_id,
        ];

        return view('user.reservation-confirm', compact('reservationData', 'selectedRoom'));
    }

    /**
     * 予約の保存
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id'    => 'required|exists:rooms,id',
            'date'       => 'required|date|after_or_equal:today',
            'periods'    => 'required|array|min:1|max:2',
            'periods.*'  => 'integer|min:1|max:6',
            'purpose'    => 'required|string|max:100',
            'members'    => 'nullable|string',
        ]);

        $periods = array_map('intval', $validated['periods']);
        sort($periods);

        for ($i = 1; $i < count($periods); $i++) {
            if ($periods[$i] - $periods[$i - 1] !== 1) {
                return back()
                    ->withErrors(['error' => '複数時限を予約する場合は、連続した時限のみ選択してください。'])
                    ->withInput();
            }
        }

        $matchingSelectingCount = Reservation::where('user_id', Auth::id())
            ->where('status', 'selecting')
            ->where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->whereIn('period', $periods)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        $hasValidSelecting = ($matchingSelectingCount === count($periods));

        if (!$hasValidSelecting) {
            Reservation::where('user_id', Auth::id())
                ->where('status', 'selecting')
                ->delete();

            return redirect()->route('user.home.index')
                ->with('error', '申請に失敗しました。選択から10分が経過したため、もう一度最初からやり直してください。');
        }

        return DB::transaction(function () use ($validated, $periods) {
            $existing = Reservation::where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('period', $periods)
                ->where('user_id', '!=', Auth::id())
                ->where(function ($query) {
                    $query->whereIn('status', ['pending', 'reserved', 'in_use'])
                        ->orWhere(function ($q) {
                            $q->where('status', 'selecting')
                                ->where('created_at', '>=', now()->subMinutes(10));
                        });
                })
                ->lockForUpdate()
                ->exists();

            $existingLesson = RoomLesson::where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('period', $periods)
                ->lockForUpdate()
                ->exists();

            if ($existing || $existingLesson) {
                $firstPeriod = min($periods);

                return redirect()->route('user.reservations.create', [
                    'room_id' => $validated['room_id'],
                    'date'    => $validated['date'],
                    'period'  => $firstPeriod,
                ])->withErrors(['error' => 'タッチの差で予約または授業が埋まってしまいました。']);
            }

            Reservation::where('user_id', Auth::id())
                ->where('status', 'selecting')
                ->where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('period', $periods)
                ->delete();

            foreach ($periods as $period) {
                Reservation::create([
                    'user_id' => Auth::id(),
                    'room_id' => $validated['room_id'],
                    'date'    => $validated['date'],
                    'period'  => $period,
                    'purpose' => $validated['purpose'],
                    'members' => $validated['members'],
                    'status'  => 'pending',
                ]);
            }

            return redirect()->route('user.home.index')
                ->with('success', '予約申請を送信しました。承認をお待ちください。');
        });
    }

    public function index()
    {
        $reservations = Reservation::where('user_id', Auth::id())
            ->where('date', '>=', Carbon::today())
            ->whereNotIn('status', ['selecting'])
            ->with('room')
            ->orderBy('date')
            ->orderBy('period')
            ->get();

        return view('user.reservations-index', compact('reservations'));
    }

    public function show(Reservation $reservation)
    {
        if ($reservation->user_id !== Auth::id()) {
            abort(403);
        }

        return view('user.reservation-detail', compact('reservation'));
    }

    public function cancel(Reservation $reservation)
    {
        if ($reservation->user_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($reservation->status, ['pending', 'reserved'], true)) {
            return back()->withErrors(['error' => 'この予約はキャンセルできません。']);
        }

        $reservation->update(['status' => 'cancelled']);

        return redirect()->route('user.reservations.index')
            ->with('success', '予約をキャンセルしました。');
    }

    public function history()
    {
        $historyReservations = Reservation::where(function ($query) {
            $query->where('date', '<', Carbon::today())
                ->orWhereIn('status', ['finished', 'cancelled']);
        })
            ->where('user_id', Auth::id())
            ->with('room')
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('user.reservations-history', compact('historyReservations'));
    }

    public function myReservations(Request $request)
    {
        $request->validate([
            'date'  => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $date  = $request->query('date');
        $limit = (int) $request->query('limit', 50);

        $q = Reservation::with('room')
            ->where('user_id', Auth::id())
            ->whereNotIn('status', ['selecting'])
            ->orderBy('date')
            ->orderBy('period');

        if ($date) {
            $q->whereDate('date', $date);
        } else {
            $q->where('date', '>=', Carbon::today());
        }

        $rows = $q->limit($limit)->get()->map(function ($r) {
            return [
                'id'         => $r->id,
                'room_name'  => optional($r->room)->name ?? '',
                'room_floor' => optional($r->room)->floor,
                'date'       => Carbon::parse($r->date)->format('Y-m-d'),
                'period'     => (int) $r->period,
                'status'     => (string) $r->status,
                'detail_url' => route('user.reservations.show', $r->id),
            ];
        });

        return response()->json($rows);
    }

    /**
     * チェックボックスON/OFF時の selecting 作成・削除
     */
    public function toggleSelecting(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date'    => 'required|date|after_or_equal:today',
            'period'  => 'required|integer|min:1|max:6',
            'action'  => 'required|in:lock,unlock',
        ]);

        if ($validated['action'] === 'unlock') {
            Reservation::where('user_id', Auth::id())
                ->where('status', 'selecting')
                ->where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->where('period', $validated['period'])
                ->delete();

            return response()->json(['success' => true, 'action' => 'unlocked']);
        }

        $result = DB::transaction(function () use ($validated) {
            $alreadyTaken = Reservation::where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->where('period', $validated['period'])
                ->where('user_id', '!=', Auth::id())
                ->where(function ($query) {
                    $query->whereIn('status', ['pending', 'reserved', 'in_use'])
                        ->orWhere(function ($q) {
                            $q->where('status', 'selecting')
                                ->where('created_at', '>=', now()->subMinutes(10));
                        });
                })
                ->lockForUpdate()
                ->exists();

            $lessonExists = RoomLesson::where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->where('period', $validated['period'])
                ->lockForUpdate()
                ->exists();

            if ($alreadyTaken || $lessonExists) {
                return false;
            }

            $alreadyMine = Reservation::where('user_id', Auth::id())
                ->where('status', 'selecting')
                ->where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->where('period', $validated['period'])
                ->where('created_at', '>=', now()->subMinutes(10))
                ->exists();

            if (!$alreadyMine) {
                Reservation::create([
                    'user_id' => Auth::id(),
                    'room_id' => $validated['room_id'],
                    'date'    => $validated['date'],
                    'period'  => $validated['period'],
                    'purpose' => '選択中',
                    'status'  => 'selecting',
                ]);
            }

            return true;
        });

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'その時間帯はすでに他の人が選択中です。',
            ], 409);
        }

        return response()->json(['success' => true, 'action' => 'locked']);
    }
}