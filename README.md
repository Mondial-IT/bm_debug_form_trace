# bm_debug_form_trace

Path: D:\Drupal_Dev\drupal11_vps01_lam_acc\docker_volume_app\git_root\web\modules\custom\bm_debug_form_trace

<h2>What it does</h2>
<p>It's main purpose is help the developer identify which form is being used.
  Identifying the form can be a difficult with Ajax forms.
  This module logs all form activities to be reviewed.
</p>
<ul>
  <li>Logs form build, validate, and submit events</li>
  <li>Captures raw AJAX and controller requests</li>
  <li>Provides a settings page with session toggle, log preview, refresh, and clear</li>
</ul>

<h2>When to use</h2>
<p>
  Enable this module only during debugging sessions. It helps diagnose problems
  with forms and imports (e.g. Gavias Page Builder).
</p>

## currently using it to fix

<h2>Steps</h2>
<ol>
  <li>Enable tracing in the module settings page.</li>
  <li>Run <code>tail -f sites/default/files/bm_debug_form_trace.log</code> to watch logs live.</li>
  <li>Perform a Gavias import while editing a translated node.</li>
  <li>Check that the log shows the correct form ID, submit handlers, and language context.</li>
</ol>

<h2>Next</h2>
<ul>
  <li>Patch Gavias code to respect translation if needed.</li>
  <li>Clear the log once finished debugging.</li>
</ul>
