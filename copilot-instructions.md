# Copilot Instructions for This Project

本プロジェクトでは、回答に **対象ファイル名（必須）** と **変更する具体的な位置** を必ず明記してください。
行番号が曖昧な場合でも、関数名・アンカーコメント・直前直後の行テキストなどで**一意に特定**してください。

---

## 回答テンプレート（必須）

**Target file (absolute from repo root):** `<例: apps/web/src/components/Timer.tsx>`

**Change scope (be precise):**
- **Location type:** `<function | method | class | block | route | Blade section | SQL | config key | etc.>`
- **Identifier:** `<例: function handleSubmit()>`
- **Anchor (if present):** `<例: // @anchor:handle-submit>`
- **Nearest unique lines (before/after):**
  - before: `<例: const isValid = form.checkValidity()>`
  - after : `<例: return false;>`
- **Expected line range:** `<例: ~L120–L140 (±5 lines OK)>`

**Patch (unified diff, include >=3 lines of context):**
```diff
--- a/<path/from/repo/root>
+++ b/<path/from/repo/root>
@@ -135,7 +135,10 @@
- if (isValid) submit(form);
+ // validate & prevent double submit
+ if (!isValid) {
+   return;
+ }
+ submit(form);
