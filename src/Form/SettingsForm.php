<?php

namespace Drupal\bm_debug_form_trace\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends FormBase {

  public function getFormId(): string {
    return 'bm_debug_form_trace_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $store = \Drupal::service('tempstore.private')->get('bm_debug_form_trace');
    $enabled = (bool) ($store->get('enabled') ?? FALSE);
    $lines = (int) ($store->get('lines') ?? 50);

    $uri  = 'public://bm_debug_form_trace.log';
    $fs   = \Drupal::service('file_system');
    $path = $fs->realpath($uri);
    $exists = $path && file_exists($path);
    $size = $exists ? filesize($path) : 0;

    $form['info'] = [
      '#type' => 'details',
      '#title' => $this->t('What this module does'),
      '#open' => TRUE,
      'body' => [
        '#markup' => $this->t('<p><strong>BM Debug Form Trace</strong> logs form builds/validations/submits and raw AJAX requests (e.g. Gavias import).</p>
        <p>Logs are written to <code>@file</code> and visible in Reports → Recent log messages (channel: <em>bm_debug_form_trace</em>).</p>
        <p><strong>Tip:</strong> Watch live via:</p>
        <pre>tail -f @path</pre>', [
          '@file' => $uri,
          '@path' => $exists ? $path : 'sites/default/files/bm_debug_form_trace.log',
        ]),
      ],
    ];

    $form['session_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable tracing for my current session'),
      '#default_value' => $enabled,
      '#description' => $this->t('Active only for your session; resets on logout or when you uncheck and save.'),
    ];

    $form['lines'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of log lines to preview'),
      '#options' => [20 => '20', 50 => '50', 100 => '100', 500 => '500'],
      '#default_value' => $lines,
    ];

    $form['log_status'] = [
      '#type' => 'item',
      '#title' => $this->t('Log file status'),
      '#markup' => $exists
        ? $this->t('File exists: @path (@size bytes).', ['@path' => $path, '@size' => $size])
        : $this->t('Log file will be created on first write.'),
    ];

    // AJAX preview container.
    $form['log_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'bm-debug-form-trace-log-preview'],
    ];

    $form['log_container']['log_preview'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Log preview (last @lines lines)', ['@lines' => $lines]),
      '#default_value' => $this->getPreview($path, $lines),
      '#attributes' => ['readonly' => 'readonly', 'style' => 'font-family: monospace; height: 400px;'],
    ];

    $form['log_container']['refresh'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh preview'),
      '#ajax' => ['callback' => '::refreshPreview', 'wrapper' => 'bm-debug-form-trace-log-preview'],
      '#limit_validation_errors' => [],
      '#submit' => [], // AJAX only
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
      '#button_type' => 'primary',
    ];

    $form['actions']['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear log file'),
      '#submit' => ['::clearLog'],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $store = \Drupal::service('tempstore.private')->get('bm_debug_form_trace');
    $enabled = (bool) $form_state->getValue('session_enable');
    $lines = (int) $form_state->getValue('lines');

    if ($enabled) {
      $store->set('enabled', TRUE);
      $this->messenger()->addStatus($this->t('Tracing enabled for your session.'));
    } else {
      $store->delete('enabled');
      $this->messenger()->addStatus($this->t('Tracing disabled for your session.'));
    }
    $store->set('lines', $lines);
  }

  public function clearLog(array &$form, FormStateInterface $form_state): void {
    $uri  = 'public://bm_debug_form_trace.log';
    $fs   = \Drupal::service('file_system');
    $path = $fs->realpath($uri);
    if ($path && file_exists($path)) {
      file_put_contents($path, '');
      $this->messenger()->addStatus($this->t('Log file cleared.'));
    } else {
      $this->messenger()->addWarning($this->t('Log file not found; nothing to clear.'));
    }
    $form_state->setRebuild(TRUE);
  }

  public function refreshPreview(array &$form, FormStateInterface $form_state): array {
    return $form['log_container'];
  }

  private function getPreview(?string $path, int $lines = 50): string {
    if ($path && file_exists($path)) {
      $content = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      $last = array_slice($content, -$lines);
      return implode("\n", $last);
    }
    return (string) $this->t('No log file found.');
  }
}
