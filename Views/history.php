<?php
/**
 * Historique des validations d'un magasin.
 *
 * @var string                     $title
 * @var array<string, mixed>       $store
 * @var list<array<string, mixed>> $checks
 * @var array<int, string>         $names
 * @var int                        $highlight
 * @var bool                       $can_approve
 * @var bool                       $can_settings
 * @var array<string, mixed>       $settings
 */
use kintai\Contract\View\Components\Flash;

$_id = (int) $store['id'];
$_success = ['submitted' => __('planning_check_flash_submitted'), 'validated' => __('planning_check_flash_validated'), 'rejected' => __('planning_check_flash_rejected'), 'resent' => __('planning_check_flash_resent')];
$_labels = ['pending' => 'planning_check_status_pending', 'submitted' => 'planning_check_status_submitted', 'validated' => 'planning_check_status_validated', 'rejected' => 'planning_check_status_rejected', 'escalated' => 'planning_check_status_escalated'];
$_errorCode = (string) ($_GET['pc_error'] ?? '');
?>
<?php include __DIR__ . "/_assets.php"; ?>
<div class="page-header">
    <h2 class="page-header__title"><?= htmlspecialchars($title) ?> — <?= htmlspecialchars((string) $store['name']) ?></h2>
    <div class="page-header__actions">
        <?php if ($can_settings): ?>
            <a href="<?= htmlspecialchars(route_url('admin.planning_check.settings', ['id' => $_id])) ?>" class="btn btn--ghost btn--sm"><?= __('planning_check_settings_title') ?></a>
        <?php endif; ?>
    </div>
</div>

<?= Flash::fromQuery('pc', $_success)->render() ?>
<?php if ($_errorCode !== ''): ?>
    <div class="alert alert--danger mb-sm"><?= htmlspecialchars(__('planning_check_err_' . preg_replace('/[^a-z_]/', '', $_errorCode))) ?></div>
<?php endif; ?>
<?php if (empty($settings['enabled'])): ?>
    <div class="alert alert--info mb-sm"><?= __('planning_check_disabled_hint') ?></div>
<?php endif; ?>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th><?= __('planning_check_col_date') ?></th>
                <th><?= __('planning_check_col_status') ?></th>
                <th><?= __('planning_check_col_submitted') ?></th>
                <th><?= __('planning_check_col_validated') ?></th>
                <th><?= __('planning_check_col_send') ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($checks === []): ?>
            <tr><td colspan="6" class="text-muted"><?= __('planning_check_history_empty') ?></td></tr>
        <?php endif; ?>
        <?php foreach ($checks as $_c): ?>
            <?php $_cid = (int) $_c['id']; ?>
            <tr<?= $_cid === $highlight ? ' class="tr--highlight"' : '' ?>>
                <td><?= htmlspecialchars((string) $_c['target_date']) ?></td>
                <td>
                    <?= __($_labels[$_c['status']] ?? 'planning_check_status_pending') ?>
                    <?php if ($_c['status'] === 'rejected' && !empty($_c['reject_reason'])): ?>
                        <div class="text-muted text-sm"><?= htmlspecialchars((string) $_c['reject_reason']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-sm"><?= $_c['submitted_by'] !== null ? htmlspecialchars(($names[$_c['submitted_by']] ?? '—') . ' · ' . substr((string) $_c['submitted_at'], 0, 16)) : '—' ?></td>
                <td class="text-sm"><?= $_c['validated_by'] !== null ? htmlspecialchars(($names[$_c['validated_by']] ?? '—') . ' · ' . substr((string) $_c['validated_at'], 0, 16)) : '—' ?></td>
                <td class="text-sm">
                    <?php if ($_c['send_status'] === 'sent'): ?>
                        ✓ <?= htmlspecialchars(substr((string) $_c['sent_at'], 0, 16)) ?>
                        <?php if ($_c['needs_resend']): ?><span class="badge badge--warning"><?= __('planning_check_changed_since') ?></span><?php endif; ?>
                    <?php elseif ($_c['send_status'] === 'failed'): ?>
                        <span class="badge badge--danger"><?= __('planning_check_send_failed') ?></span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-right">
                    <a href="<?= htmlspecialchars(route_url('admin.planning_check.pdf', ['id' => $_id, 'cid' => $_cid])) ?>" class="btn btn--ghost btn--sm">PDF</a>
                    <?php if ($can_approve && $_c['needs_resend']): ?>
                        <form method="POST" action="<?= htmlspecialchars(route_url('admin.planning_check.resend', ['id' => $_id, 'cid' => $_cid])) ?>" class="inline-form">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn--primary btn--sm"><?= $_c['send_status'] === 'failed' ? __('planning_check_retry') : __('planning_check_resend_updated') ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php unset($_c, $_cid); ?>
