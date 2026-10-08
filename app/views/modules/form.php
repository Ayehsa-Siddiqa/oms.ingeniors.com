<?php
$validEmpIds = array_map('intval', array_column($employees ?? [], 'id'));
$assignedEmployeeIds = array_values(array_intersect(array_map('intval', $assignedEmployeeIds ?? []), $validEmpIds));
?>
<form class="card" method="post" enctype="multipart/form-data" action="<?= empty($record) ? url($module) : url($module . '/' . $record['id'] . '/update') ?>">
    <?= csrf_field() ?>
    <div class="card-header"><strong><?= empty($record) ? 'Create' : 'Edit' ?> <?= e($config['title']) ?></strong></div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($config['fields'] as $name => $field): ?>
                <?php if ($name === 'assigned_to' && ($module === 'projects' || $module === 'pending-tasks')): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?= e($field['label'] ?? 'Assigned To') ?></label>
                        <div class="oms-multiselect" id="assignedToWrapper">
                            <!-- Hidden inputs are submitted as employee_ids[] -->
                            <?php foreach ($assignedEmployeeIds as $eid): ?>
                                <input type="hidden" name="employee_ids[]" value="<?= (int) $eid ?>" class="ms-hidden-input">
                            <?php endforeach; ?>

                            <!-- Tag display + trigger -->
                            <div class="ms-trigger" id="msTrigger" tabindex="0">
                                <div class="ms-tags" id="msTags">
                                    <?php if (empty($assignedEmployeeIds)): ?>
                                        <span class="ms-placeholder" id="msPlaceholder">Select team members…</span>
                                    <?php else: ?>
                                        <?php foreach ($employees as $emp):
                                            if (!in_array((int)$emp['id'], $assignedEmployeeIds, true)) continue; ?>
                                            <span class="ms-tag" data-id="<?= (int)$emp['id'] ?>">
                                                <?= e($emp['name']) ?>
                                                <button type="button" class="ms-tag-remove" data-id="<?= (int)$emp['id'] ?>">&times;</button>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <i class="bi bi-chevron-down ms-chevron" id="msChevron"></i>
                            </div>

                            <!-- Dropdown panel -->
                            <div class="ms-dropdown" id="msDropdown" style="display:none;">
                                <div class="ms-search-wrap">
                                    <i class="bi bi-search ms-search-icon"></i>
                                    <input type="text" class="ms-search" id="msSearch" placeholder="Search team members…">
                                </div>
                                <div class="ms-options" id="msOptions">
                                    <?php foreach ($employees as $emp):
                                        $checked = in_array((int)$emp['id'], $assignedEmployeeIds, true); ?>
                                        <label class="ms-option <?= $checked ? 'ms-option-checked' : '' ?>" data-id="<?= (int)$emp['id'] ?>">
                                            <span class="ms-checkbox <?= $checked ? 'checked' : '' ?>">
                                                <?= $checked ? '<i class="bi bi-check2"></i>' : '' ?>
                                            </span>
                                            <span class="ms-option-text">
                                                <strong><?= e($emp['name']) ?></strong>
                                                <small><?= e($emp['designation']) ?><?= !empty($emp['department']) ? ', ' . e($emp['department']) : '' ?></small>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-text">Click to select one or more team members.</div>
                    </div>
                <?php elseif (($field['type'] ?? '') === 'virtual'): ?>
                    <?php continue; ?>
                <?php else: ?>
                    <div class="<?= ($field['type'] ?? '') === 'textarea' ? 'col-12' : 'col-md-6' ?>">
                        <label class="form-label"><?= e($field['label']) ?><?= !empty($field['required']) ? ' *' : '' ?></label>
                        <?php if (($field['type'] ?? '') === 'textarea'): ?>
                            <textarea class="form-control" name="<?= e($name) ?>" rows="4"><?= e($record[$name] ?? '') ?></textarea>
                        <?php elseif (($field['type'] ?? '') === 'roles_select'): ?>
                            <select class="form-select" name="<?= e($name) ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                                <option value="">— Select Role —</option>
                                <?php foreach (($roles ?? []) as $r): ?>
                                    <option value="<?= (int)$r['id'] ?>" <?= ((int)($record[$name] ?? 0) === (int)$r['id']) ? 'selected' : '' ?>>
                                        <?= e($r['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif (in_array($field['type'] ?? '', ['department_select', 'departments_select'])): ?>
                            <select class="form-select" name="<?= e($name) ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                                <option value="">— Select Department —</option>
                                <?php
                                $currentDept = (string)($record[$name] ?? '');
                                $hasCurrent = false;
                                foreach (($departments ?? []) as $dept):
                                    $dName = (string)($dept['department_name'] ?? '');
                                    if ($currentDept !== '' && $currentDept === $dName) {
                                        $hasCurrent = true;
                                    }
                                ?>
                                    <option value="<?= e($dName) ?>" <?= ($currentDept === $dName) ? 'selected' : '' ?>>
                                        <?= e($dName) ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if ($currentDept !== '' && !$hasCurrent): ?>
                                    <option value="<?= e($currentDept) ?>" selected>
                                        <?= e($currentDept) ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                        <?php elseif (($field['type'] ?? '') === 'select'): ?>
                            <select class="form-select" name="<?= e($name) ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                                <?php foreach ($field['options'] as $option): ?>
                                    <option value="<?= e($option) ?>" <?= (($record[$name] ?? '') === $option) ? 'selected' : '' ?>><?= e($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif (($field['type'] ?? '') === 'file'): ?>
                            <input class="form-control" name="<?= e($name) ?>" type="file" accept=".pdf,.doc,.docx,.dot,.dotx,.docm,.rtf,.txt,.md,.log,.json,.xml,.xls,.xlsx,.xlsm,.xlsb,.csv,.ppt,.pptx,.pps,.ppsx,.pot,.potx,.png,.jpg,.jpeg,.webp,.gif,.bmp,.svg,.zip,.rar,.7z,.tar,.gz,.dwg,.dxf">
                            <input type="hidden" name="existing_<?= e($name) ?>" value="<?= e($record[$name] ?? '') ?>">
                            <?php if (!empty($record[$name])): ?>
                                <div class="form-text mt-2 d-flex flex-wrap align-items-center gap-2">
                                    <span class="text-body"><i class="bi bi-paperclip me-1"></i>Current file: <strong><?= e($record[$name]) ?></strong></span>
                                    <a class="btn btn-sm btn-outline-primary py-0 px-2" href="<?= asset('uploads/' . $record[$name]) ?>" download target="_blank">
                                        <i class="bi bi-download me-1"></i>Download
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="form-text text-muted">Supports Word (.docx, .doc), Excel (.xlsx, .xls, .csv), PowerPoint (.pptx, .ppt), PDF, Images, Text (.txt, .md), ZIP, and CAD files.</div>
                        <?php elseif (($field['type'] ?? '') === 'password'): ?>
                            <div class="input-group">
                                <input class="form-control" name="<?= e($name) ?>" type="password" id="input_<?= e($name) ?>" value="" <?= !empty($field['required']) ? 'required' : '' ?> placeholder="Enter password">
                                <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="input_<?= e($name) ?>" title="Show / Hide Password" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?php if (!empty($record)): ?>
                                <div class="form-text">Leave blank to keep the current password.</div>
                            <?php endif; ?>
                        <?php else: 
                            $isEmpEmail = ($module === 'employees' && $name === 'email' && !empty($record));
                            $isReadOnly = !empty($field['readonly']) || $isEmpEmail;
                        ?>
                            <input class="form-control" name="<?= e($name) ?>" type="<?= e($field['type'] ?? 'text') ?>" value="<?= e($record[$name] ?? '') ?>" <?= !empty($field['required']) ? 'required' : '' ?> <?= $isReadOnly ? 'readonly style="background-color: var(--oms-panel); cursor: not-allowed; opacity: 0.85;"' : '' ?>>
                            <?php if ($isEmpEmail): ?>
                                <div class="form-text text-muted"><i class="bi bi-lock-fill me-1 text-warning"></i>Email is unchangeable here. To update this employee's email, edit the user in the <strong>Users</strong> tab.</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="<?= url($module) ?>">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
</form>

