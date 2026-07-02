Step 1:
1. Wrap class declarations for all classes (`Aialtg_Image_Descriptor`, `Aialtg_Settings`, `Aialtg_Generator`, `Aialtg_Cron`) in `class_exists` conditional checks to prevent redeclaration fatal errors.
2. Cast setting option arrays returned by `get_option` to `array` before accessing offsets, completely eliminating PHP 8.0+ notices and warnings for unset options.
3. Wrap all AJAX endpoint handlers in `try-catch` blocks catching `Throwable` errors to return structured JSON errors instead of unhandled 500 crashes.
4. Enhance generation capability checks to enforce `edit_post` permission on the specific attachment ID rather than only the general `upload_files` check.
5. Optimize JSON error fixing routine to bulk delete post metadata via a single SQL query rather than running queries inside a loop, preventing timeouts.
6. Add Base64 fallback encoding support for images on local development servers, bypassing OpenRouter's HTTP download errors for non-public URLs.
7. Build `robust_json_decode` to strip markdown tags cleanly, clean up trailing commas, and parse JSON correctly, avoiding fallback corrupt writes.
8. Set dynamic execution time limits (`@set_time_limit( 60 )`) before processing each image in the background cron worker to prevent Max Execution Time limits.
9. Cast inputs of `str_replace` to `string` in prompt parsing to avoid PHP 8.1+ deprecation warnings on null values.

Commit message: refactor(core): harden codebase against PHP warnings, errors, and execution timeouts

Step 2:
1. Increment version to 1.7.1 in main plugin header and asset enqueues.
2. Increment stable tag to 1.7.1 in readme.txt and document changes in its changelog section.
Commit message: bump(version): increment plugin version to 1.7.1

Step 3:
1. Redesign the plugin settings dashboard with a modern two-column layout.
2. Style fields, textareas, and switches with custom SaaS indigo/violet gradients, bouncy spring animations, and glow-ring focus highlights.
3. Move statistics calculations to a dedicated helper and place the stats card in the sidebar.
4. Add password visibility toggle to the OpenRouter API Key input.
5. Create a dependency-free custom canvas confetti generator and completion badge to celebrate 100% processing completion.
Commit message: feat(ui): redesign settings dashboard to modern SaaS theme and add confetti milestone celebration
