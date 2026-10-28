<?php

namespace HW\UI\Forms\Controls;

use HW\UI\Forms\Traits\HasColumns;
use HW\UI\Forms\Traits\HasSchema;

class Repeater extends FormControl {

    use HasSchema;
    use HasColumns;

    protected function getType(): string {
        return 'repeater';
    }

    /**
     * Rewrite name/id/for attributes in captured HTML to apply a namespace.
     * e.g. name="title" -> name="gallery[0][title]"
     *      id="title"   -> id="gallery_0_title"
     *
     * @param string $html
     * @param string $repeaterName
     * @param string $index  Use '__INDEX__' for the template placeholder.
     * @return string
     */
    private function applyNamespace(string $html, string $repeaterName, string $index): string {

        $html = preg_replace_callback(
            '/\bname="([^"]+)"/',
            fn($m) => 'name="' . $repeaterName . '[' . $index . '][' . $m[1] . ']"',
            $html
        );

        $html = preg_replace_callback(
            '/\bid="([^"]+)"/',
            fn($m) => 'id="' . $repeaterName . '_' . $index . '_' . $m[1] . '"',
            $html
        );

        $html = preg_replace_callback(
            '/\bfor="([^"]+)"/',
            fn($m) => 'for="' . $repeaterName . '_' . $index . '_' . $m[1] . '"',
            $html
        );

        return $html;
    }

    /**
     * Render schema controls as a template string with __INDEX__ placeholder.
     * Used by Alpine.js when adding new rows.
     * Includes drag handle and hidden position input.
     *
     * @return string
     */
    private function getSchemaTemplate(): string {

        ob_start();

        echo '<div class="hw-repeater__handle text-secondary mb-2" title="Drag to reorder">⠿ <span class="hw-repeater__row-number">__POSITION__</span></div>';

        if ($this->getColumns() > 1) {
            echo '<div class="row g-3">';
        }

        foreach ($this->getSchema() as $control) {
            if ($this->getColumns() > 1) echo '<div class="' . $control->getColClass($this->getColumns()) . '">';
            $control->render();
            if ($this->getColumns() > 1) echo '</div>';
        }

        if ($this->getColumns() > 1) {
            echo '</div>';
        }

        echo '<input type="hidden" name="_position" value="0" class="hw-repeater__position">';

        $html = ob_get_clean();

        return $this->applyNamespace($html, $this->getName(), '__INDEX__');
    }

    /**
     * Render schema controls for a specific existing row with its saved data.
     * Includes drag handle and hidden position input initialised to the row index.
     *
     * @param int   $index
     * @param array $rowData
     * @return void
     */
    private function renderSchemaRow(int $index, array $rowData): void {

        ob_start();

        echo '<div class="hw-repeater__handle text-secondary mb-2" title="Drag to reorder">⠿ <span class="hw-repeater__row-number">' . ($index + 1) . '</span></div>';

        if ($this->getColumns() > 1) {
            echo '<div class="row g-3">';
        }

        foreach ($this->getSchema() as $control) {
            if ($this->getColumns() > 1) echo '<div class="' . $control->getColClass($this->getColumns()) . '">';
            $fieldName     = $control->getName();
            $originalValue = $control->getValue();
            $control->value($rowData[$fieldName] ?? null);
            $control->render();
            $control->value($originalValue);
            if ($this->getColumns() > 1) echo '</div>';
        }

        if ($this->getColumns() > 1) {
            echo '</div>';
        }

        echo '<input type="hidden" name="_position" value="' . $index . '" class="hw-repeater__position">';

        $html = ob_get_clean();

        echo $this->applyNamespace($html, $this->getName(), (string) $index);
    }

    /**
     * Render the repeater control.
     * - Existing rows are server-rendered and managed via Alpine x-if.
     * - New rows are injected via Alpine x-html using the template.
     * - The HTML template is stored in a <template> tag to avoid JSON-encoding issues.
     * - Sorting is powered by SortableJS (must be enqueued separately as 'sortablejs').
     *   After each sort/add/remove the hidden _position inputs are updated so the server
     *   can determine the intended order regardless of array key.
     *
     * @return void
     */
    protected function renderControl(): void {

        $saved         = $this->getValue();
        $value         = is_array($saved) ? $saved : [];
        $existingCount = count($value);
        $rowIndices    = json_encode(array_keys($value));
        $repeaterName  = $this->getName();
        $templateId    = 'hw-repeater-tpl-' . $repeaterName;
        $templateHtml  = $this->getSchemaTemplate();
        ?>

        <template id="<?= esc_attr($templateId) ?>">
            <?= $templateHtml ?>
        </template>

        <div
            class="hw-repeater"
            x-data="{
                rows: <?= $rowIndices ?>,
                nextIndex: <?= $existingCount ?>,
                addRow() {
                    this.rows.push(this.nextIndex++);
                    this.$nextTick(() => this.recalcPositions());
                },
                removeRow(i) {
                    this.rows = this.rows.filter(r => r !== i);
                    this.$nextTick(() => this.recalcPositions());
                },
                getRowHtml(i) {
                    const pos = this.rows.indexOf(i) + 1;
                    return document.getElementById('<?= esc_js($templateId) ?>').innerHTML
                        .replaceAll('__INDEX__', i)
                        .replaceAll('__POSITION__', pos);
                },
                recalcPositions() {
                    this.$el.querySelectorAll('.hw-repeater__item').forEach((item, pos) => {
                        const inp = item.querySelector('.hw-repeater__position');
                        if (inp) inp.value = pos;
                        const num = item.querySelector('.hw-repeater__row-number');
                        if (num) num.textContent = pos + 1;
                    });
                }
            }"
        >
            <div
                class="hw-repeater__items"
                x-init="
                    Sortable.create($el, {
                        handle: '.hw-repeater__handle',
                        animation: 150,
                        ghostClass: 'hw-repeater__ghost',
                        onEnd: () => recalcPositions()
                    })
                "
            >

                <?php foreach ($value as $index => $rowData): ?>
                <template x-if="rows.includes(<?= intval($index) ?>)">
                    <div
                        class="hw-repeater__item position-relative border rounded p-3 mb-2"
                        x-data="{ hovered: false }"
                        @mouseenter="hovered = true"
                        @mouseleave="hovered = false"
                    >
                        <?php $this->renderSchemaRow($index, (array) $rowData); ?>
                        <button
                            type="button"
                            class="btn btn-sm btn-link text-danger position-absolute top-0 end-0 m-2"
                            x-show="hovered"
                            x-transition.opacity
                            @click="removeRow(<?= intval($index) ?>)"
                        >
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </template>
                <?php endforeach; ?>

                <template x-for="index in rows.filter(i => i >= <?= $existingCount ?>)" :key="index">
                    <div
                        class="hw-repeater__item position-relative border rounded p-3 mb-2"
                        x-data="{ hovered: false }"
                        @mouseenter="hovered = true"
                        @mouseleave="hovered = false"
                    >
                        <div x-init="$el.innerHTML = getRowHtml(index)"></div>
                        <button
                            type="button"
                            class="btn btn-sm btn-link text-danger position-absolute top-0 end-0 m-2"
                            x-show="hovered"
                            x-transition.opacity
                            @click="removeRow(index)"
                        >
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </template>

            </div>

            <button type="button" class="btn btn-sm btn-primary mt-2" @click="addRow()">
                + Add Item
            </button>
        </div>
        <?php
    }
}
