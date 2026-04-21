## 2026-04-21

- Fixed reflected XSS: added `esc_attr()` to `$value['invoice']` and `$value['amount']` in HTML value attributes (lines 149, 154)
- Fixed PHP 8 warnings: replaced bare `$_GET['invoice']` and `$_GET['amount']` ternary checks with `! empty()` (lines 143-144)
- Added `esc_attr()` to `$unique_id` in all name/id/for HTML attributes (defense-in-depth)
- Fixed functional bug: amount input had duplicate name/id using `_invoice` suffix; corrected to `_amount`

References: https://github.com/proudcity/wp-proudcity/issues/2802
