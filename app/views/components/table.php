<?php
/**
 * Reusable Table Component
 * 
 * @var array|null $headers Optional array of header labels or ['label' => '', 'class' => '']
 * @var array|null $rows Optional 2D array of data rows
 * @var callable|null $rowRenderer Optional custom row rendering function fn($row, $index)
 * @var string|null $slot Optional raw HTML body content
 * @var string|null $emptyMessage Empty state message (default: 'No records found.')
 * @var string|null $tableClass CSS class for <table> element
 * @var string|null $wrapperClass CSS class for scroll wrapper
 * @var string|null $tableAttributes Extra HTML attributes for <table>
 */

$headers = $headers ?? [];
$rows = $rows ?? null;
$rowRenderer = $rowRenderer ?? null;
$slot = $slot ?? $content ?? null;
$emptyMessage = $emptyMessage ?? 'No records found.';
$tableClass = $tableClass ?? 'table align-middle mb-0';
$wrapperClass = $wrapperClass ?? '';
$colCount = count($headers) ?: 1;
?>
<div class="table-responsive oms-table-wrapper <?= e($wrapperClass) ?>" tabindex="0">
    <table class="<?= e($tableClass) ?>" <?= $tableAttributes ?? '' ?>>
        <?php if (!empty($headers)): ?>
            <thead>
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <?php if (is_array($header)): ?>
                            <th class="<?= e($header['class'] ?? '') ?>" <?= $header['attrs'] ?? '' ?>>
                                <?= e($header['label'] ?? '') ?>
                            </th>
                        <?php else: ?>
                            <th><?= e($header) ?></th>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
        <?php endif; ?>
        <tbody>
            <?php if ($slot !== null): ?>
                <?= $slot ?>
            <?php elseif ($rows !== null): ?>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $index => $row): ?>
                        <?php if ($rowRenderer && is_callable($rowRenderer)): ?>
                            <?= $rowRenderer($row, $index) ?>
                        <?php elseif (is_array($row)): ?>
                            <tr>
                                <?php foreach ($row as $cell): ?>
                                    <td><?= is_scalar($cell) ? e((string)$cell) : '' ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php else: ?>
                            <tr><td><?= e((string)$row) ?></td></tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= $colCount ?>" class="text-center text-muted py-4">
                            <?= e($emptyMessage) ?>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
