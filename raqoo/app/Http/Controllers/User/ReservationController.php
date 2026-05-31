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
     * 選択された時限の中に、既に終了時刻を過ぎているものがあるか判定する
     * @param string $date 予約対象日 (Y-m-d)
     * @param array $periods 判定対象の時限IDの配列
     * @return bool ひとつでも終了済みの時限があればtrue
     **/
    // 終了時刻を過ぎていたらアウト（true）を返す
    private function hasEndedPeriod(string $date, array $periods): bool  // arrayなのは予約が連続2コマの場合もあるため
    {
        // 予約対象日が「今日」でない場合は、時間の判定自体が不要のためfalse
        // today()は日付を軸に取得 (ex)2026-05-31 00:00:00
        if (!Carbon::parse($date)->isSameDay(Carbon::today())) {
            return false;
        }

        // 各時限ごとに限時刻が終了時間を過ぎていないか確認
        foreach ($periods as $period) {
            $endTimeConfig = config("period.{$period}.end_time");

            // 終了時刻が設定されていなかったらスキップして次のperiodを見に行く
            if (!$endTimeConfig) {
                continue;
            }

            $periodEndAt = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$endTimeConfig}");

            // 「現在時刻 >= 終了時刻」であれば、その時限は終了済みとみなす
            // now()は今個の週間、日付＋秒単位で取得 (ex)2026-05-31 22:25:03
            if (now()->greaterThanOrEqualTo($periodEndAt)) {
                return true;  // 終了済みがあればtrueを返す
            }
        }

        // 全ての時限が終了前であればセーフ（false）を返す
        return false;
    }

    /**
     * 予約申請フォームの表示
     * フォームを開いた時点で selecting レコードを作成して一時ロックする
     */
    public function create(Request $request)
    {

        /**
         * 今日の予約がすでにある場合、その終了時刻に達するまでその日の新たな予約はできないようにする
         */
        //TODO: この「終了時刻まで予約ブロック処理」と、「二週間先のブロック処理」を関数にまとめる
        //TODO: 2限連続で予約された場合の対処p_minとp_max ← これ優先的にテストしたほうがいい

        // 今日の予約があるかチェック
        $todayReservations = Reservation::where('user_id', Auth::id())
            ->whereDate('date', today())
            ->whereIn('status', ['pending', 'reserved', 'in_use'])
            ->get();

        // 今日の自分の予約に対して1件ずつチェック
        foreach ($todayReservations as $reservation) {
            $endTimeConfig = config("period.{$reservation->period}.end_time");
            // 終了時刻がconfigに存在しない場合は、この1件はブロックして次の予約をチェックする
            if (!$endTimeConfig) {
                continue;
            }

            // 日付（HH:MM）に変換
            $endTime = Carbon::parse($endTimeConfig);

            // すでに今日の予約がある場合：現在の時刻が終了時刻より前（現在時刻 < 終了時刻）なら新規予約ブロックする
            if (Carbon::parse($request->date)->isSameDay(Carbon::today()) && now()->lessThan($endTime)) {
                return redirect()
                    ->route('user.home.index')
                    ->with('error', '今日の予約の利用が終了してから新たに予約してください');
            }
        }

        // URL直叩きブロック処理
        $period = (int) $request->period;
        if ($period && $this->hasEndedPeriod($request->date, [$period])) {
            return redirect()
                ->route('user.home.index')
                ->with('error', '選択された時限はすでに終了しています。別の日時を選んでください。');
        }

        // 明日以降の各日は2件以上の予約不可 1件までしか予約できません！
        $date = $request->date;
        if (Carbon::parse($request->date)->greaterThan(Carbon::today())) {
            $isExist = Reservation::where('user_id', Auth::id())
                ->where('date', $date)
                ->whereIn('status', ['pending', 'reserved', 'in_use'])
                ->exists();
            if ($isExist) {
                return redirect()
                    ->route('user.home.index')
                    ->with('error', '1件すでに予約が入っているのでできません');
            }
        }


        /**
         * 今日から二週間分（14日分）の予定だけ表示させる
         */
        $maxDate = Carbon::today()->addDays(14)->toDateString();
        $selectedRoom = $request->room_id ? Room::find($request->room_id) : null;

        if (!$selectedRoom) {
            return redirect()->route('user.home.index')
                ->with('error', '教室がみつかりません。');
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

        // 2週間を超えていたらエラー表示
        if ($request->date > $maxDate) {
            return redirect()->route('user.home.index')
                ->with('error', '予約できるのは2週間先までです。');
        }


        /**
         * 予約と授業の取得
         */
        $date   = $request->date;
        $room   = $selectedRoom;
        $period = (int) $request->period;

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

        // URL直叩きブロック処理
        if ($this->hasEndedPeriod($validated['date'], $validated['periods'])) {
            return back()
                ->withErrors(['periods' => '選択された時限は既に終了しています。別の時間を選択してください。'])
                ->withInput();
        }

        $selectedRoom = Room::find($validated['room_id']);

        if (!$selectedRoom) {
            return redirect()->route('user.home.index')
                ->with('error', '教室がみつかりません。');
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
            ->exists();

        $existingLesson = RoomLesson::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->whereIn('period', $periods)
            ->exists();

        if ($existing || $existingLesson) {
            return back()
                ->withErrors(['periods' => '選択された時間帯の中に、すでに予約済みまたは授業が登録されている枠が含まれています。'])
                ->withInput();
        }

        // 複数時限の場合、足りない selecting を追加で作成する
        if (count($periods) > 1) {
            $existingSelectingPeriods = Reservation::where('user_id', Auth::id())
                ->where('status', 'selecting')
                ->where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('period', $periods)
                ->where('created_at', '>=', now()->subMinutes(10))
                ->pluck('period')
                ->map(fn($period) => (int) $period)
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
            'room_id'    => 'required|exists:rooms,id',  // ちゃんと教室存在してるか
            'date'       => 'required|date|after_or_equal:today',  // 今日以降
            // ▼ Copilot指摘対応: こちらにも max:2 を追加
            'periods'    => 'required|array|min:1|max:2',  // 最低1コマ、最大2コマ
            'periods.*'  => 'integer|min:1|max:6',
            'purpose'    => 'required|string|max:100',
            'members'    => 'nullable|string', // 利用人数は空でもOK（nullable
        ]);

        // URL直叩きブロック処理
        if ($this->hasEndedPeriod($validated['date'], $validated['periods'])) {
            return back()
                ->withErrors(['periods' => '選択された時限は既に終了しています。別の時間を選択してください。'])
                ->withInput();
        }

        $periods = $validated['periods'];  // バリデーション（型）が正しいことが証明されたやつだけを使う
        sort($periods);  // 下の$periods[$i] - $periods[$i - 1] !== 1が正しく動作するために昇順に並べ替え

        for ($i = 1; $i < count($periods); $i++) {
            if ($periods[$i] - $periods[$i - 1] !== 1) {
                return back()
                    ->withErrors(['error' => '複数時限を予約する場合は、連続した時限のみ選択してください。'])
                    ->withInput();
            }
        }

        return DB::transaction(function () use ($validated) {
            // 保存直前の重複チェック（今選択している時限の部屋に他の予約がないかチェック）
            $existing = Reservation::where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('period', $validated['periods'])
                ->where('status', '!=', 'cancelled') // キャンセル以外(pending, in_use, reserved)が存在していないか→存在していたら予約できない＝重複になってしまうため
                ->lockForUpdate()
                ->exists();

            // 同一ユーザーの同時刻の別教室予約の重複チェック
            $conflict = Reservation::where('user_id', Auth::id())
                ->where('date', $validated['date'])
                ->whereIn('period', $validated['periods'])
                ->whereIn('status', ['pending', 'reserved', 'in_use'])
                ->where('room_id', '!=', $validated['room_id']) // 今予約しようとしている部屋「以外」ですでに予約をもっていないか
                ->lockForUpdate()
                ->exists();

            // 授業が登録（存在）されているかチェック
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

            if ($conflict) {
                return redirect()->route('user.reservations.create', [
                    'room_id' => $validated['room_id'],
                    'date' => $validated['date'],
                    'period' => $validated['periods'][0] ?? null, // create手側がもしperiodを期待しているなら
                ])->withErrors(['error' => '同時刻にすでにあなたの予約があるため予約できません']);
            }

            // ↑の代替案　リダイレクト方法が異なる
            // if ($conflict) {
            //     return back()->withErrors(['error' => '同時刻にすでに予約（申請含む）があるため予約できません。'])->withInput();
            // }

            foreach ($validated['periods'] as $period) {
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

    // --- 以降の index, show, cancel, history, myReservations は変更なし ---

    /**
     * 予約詳細
     */
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

    /**
     * 学生側の予約キャンセル処理
     */
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

    /**
     * 予約履歴
     */
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

    /**
     * 予約一覧
     */
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

        $this->cleanupExpiredSelecting();

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
