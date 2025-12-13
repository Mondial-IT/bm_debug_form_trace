# bm_debug_form_trace

## What it does
<p>It's main purpose is help the developer identify which form is being used.
  Identifying the form can be a difficult with Ajax forms.
  This module logs all form activities to be reviewed.
</p>
<ul>
  <li>Logs form build, validate, and submit events</li>
  <li>Captures raw AJAX and controller requests</li>
  <li>Provides a settings page with session toggle, log preview, refresh, and clear</li>
</ul>

## When to use
<p>
  Enable this module only during debugging sessions. It helps diagnose problems
  with forms and imports (e.g. Gavias Page Builder).
</p>

## Usage
  * Enable tracing in the module settings page. `admin/bluemarloc/BM Debug Form Trace`
  * switch to the page with the form, refresh and enter
  * switch back and view the form id information
  * or Run `tail -f sites/default/files/bm_debug_form_trace.log` to watch logs live.
  * Example *Perform a Gavias import while editing a translated node.*
  * Check that the log shows the correct form ID, submit handlers, and language context.

# issue todo
<ul>
  <li>Patch Gavias code to respect translation if needed.</li>
  <li>Clear the log once finished debugging.</li>
</ul>

## Example output

![bm_debug_form_trace_example](https://github.com/user-attachments/assets/bb8a7fe9-ebf9-443b-bf2a-ec59deea52cd)
