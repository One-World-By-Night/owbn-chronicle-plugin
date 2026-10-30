<?php
/**
 * HST & AST Report
 *
 * Admin-only master list of every active (published) chronicle with its Head
 * Storyteller, its Assistant Storytellers and their email addresses, and the
 * date the record was last updated. Shown as a filterable, sortable table with
 * a CSV export, for checking against the Storyteller email list.
 */

defined('ABSPATH') || exit;

add_action('admin_menu', 'owbn_hst_ast_register_menu');
add_action('admin_post_owbn_hst_ast_csv', 'owbn_hst_ast_handle_csv');

/**
 * Whether the current user may see the report (administrator, exec_team, web_team).
 *
 * @return bool
 */
function owbn_hst_ast_can_view()
{
    return function_exists('owbn_is_admin_user') && owbn_is_admin_user();
}

/**
 * Adds the report under OWBN C&C on the site that holds the chronicle records.
 */
function owbn_hst_ast_register_menu()
{
    if (!owbn_hst_ast_can_view()) {
        return;
    }

    $counts = wp_count_posts('owbn_chronicle');
    if (empty($counts->publish)) {
        return;
    }

    add_submenu_page(
        'owbn-cc',
        __('HST & AST Report', 'owbn-chronicle-manager'),
        __('HST & AST Report', 'owbn-chronicle-manager'),
        'read',
        'owbn-cc-hst-ast',
        'owbn_render_hst_ast_page'
    );
}

/**
 * Reads the name and actual email from a stored staff entry (hst_info or one ast_list row).
 *
 * @param mixed $info Stored entry.
 * @return array{name:string,email:string}
 */
function owbn_hst_ast_person($info)
{
    $info = is_array($info) ? $info : array();

    return array(
        'name'  => trim((string) ($info['display_name'] ?? '')),
        'email' => trim((string) ($info['actual_email'] ?? '')),
    );
}

/**
 * Lists every published chronicle, in title order, with its HST, its ASTs and its last-updated date.
 *
 * @return array[]
 */
function owbn_hst_ast_report_rows()
{
    $posts = get_posts(array(
        'post_type'      => 'owbn_chronicle',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ));

    $rows = array();
    foreach ($posts as $post) {
        $staff = array();
        $asts  = get_post_meta($post->ID, 'ast_list', true);
        if (is_array($asts)) {
            foreach ($asts as $ast) {
                $person = owbn_hst_ast_person($ast);
                if ($person['name'] !== '' || $person['email'] !== '') {
                    $staff[] = $person;
                }
            }
        }

        $rows[] = array(
            'chronicle_id' => (int) $post->ID,
            'chronicle'    => wp_specialchars_decode($post->post_title, ENT_QUOTES),
            'slug'         => (string) get_post_meta($post->ID, 'chronicle_slug', true),
            'hst'          => owbn_hst_ast_person(get_post_meta($post->ID, 'hst_info', true)),
            'staff'        => $staff,
            'updated'      => mysql2date('Y-m-d', $post->post_modified),
        );
    }

    return $rows;
}

/**
 * Whether a person lacks the name or the email needed for the list check.
 *
 * @param array $person Person from owbn_hst_ast_person().
 * @return bool
 */
function owbn_hst_ast_person_incomplete(array $person)
{
    return $person['name'] === '' || $person['email'] === '';
}

/**
 * Whether the HST or any AST of a row lacks a name or an email.
 *
 * @param array $row Report row.
 * @return bool
 */
function owbn_hst_ast_row_incomplete(array $row)
{
    if (owbn_hst_ast_person_incomplete($row['hst'])) {
        return true;
    }
    foreach ($row['staff'] as $person) {
        if (owbn_hst_ast_person_incomplete($person)) {
            return true;
        }
    }
    return false;
}

/**
 * Plain-text form of a person for the CSV: "Name <email>", with gaps spelled out.
 *
 * @param array $person Person from owbn_hst_ast_person().
 * @return string
 */
function owbn_hst_ast_person_text(array $person)
{
    if ($person['name'] === '' && $person['email'] === '') {
        return '(none listed)';
    }
    if ($person['email'] === '') {
        return $person['name'] . ' (no email)';
    }
    if ($person['name'] === '') {
        return '(no name) <' . $person['email'] . '>';
    }
    return $person['name'] . ' <' . $person['email'] . '>';
}

/**
 * HTML form of a person for the table: name over email, gaps flagged.
 *
 * @param array $person Person from owbn_hst_ast_person().
 * @return string
 */
function owbn_hst_ast_person_html(array $person)
{
    $name  = $person['name'] !== ''
        ? esc_html($person['name'])
        : '<span class="owbn-hst-ast-missing">' . esc_html__('No name', 'owbn-chronicle-manager') . '</span>';
    $email = $person['email'] !== ''
        ? esc_html($person['email'])
        : '<span class="owbn-hst-ast-missing">' . esc_html__('No email', 'owbn-chronicle-manager') . '</span>';

    return '<div class="owbn-hst-ast-person"><span class="owbn-hst-ast-name">' . $name . '</span> <span class="owbn-hst-ast-email">' . $email . '</span></div>';
}

/**
 * Neutralizes a CSV cell that a spreadsheet would read as a formula.
 *
 * @param string $value Cell text.
 * @return string
 */
function owbn_hst_ast_csv_cell($value)
{
    $value = (string) $value;
    if ($value !== '' && preg_match('/^[=+\-@\t\r]/', $value)) {
        return "'" . $value;
    }
    return $value;
}

/**
 * Builds the CSV: a header row then one row per chronicle, same columns as the table.
 *
 * @param array[] $rows Rows from owbn_hst_ast_report_rows().
 * @return array[]
 */
function owbn_hst_ast_csv_rows(array $rows)
{
    $csv = array(array('Chronicle', 'HST (email)', 'Staff (emails)', 'Last Updated'));

    foreach ($rows as $row) {
        $staff = array();
        foreach ($row['staff'] as $person) {
            $staff[] = owbn_hst_ast_person_text($person);
        }
        $csv[] = array_map('owbn_hst_ast_csv_cell', array(
            $row['chronicle'],
            owbn_hst_ast_person_text($row['hst']),
            implode('; ', $staff),
            $row['updated'],
        ));
    }

    return $csv;
}

/**
 * Streams the report as a UTF-8 CSV download.
 */
function owbn_hst_ast_handle_csv()
{
    if (!owbn_hst_ast_can_view()) {
        wp_die(esc_html__('You do not have permission to export this report.', 'owbn-chronicle-manager'), '', array('response' => 403));
    }
    check_admin_referer('owbn_hst_ast_csv');

    $csv = owbn_hst_ast_csv_rows(owbn_hst_ast_report_rows());

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="owbn-hst-ast-' . wp_date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    foreach ($csv as $line) {
        fputcsv($out, $line, ',', '"', '');
    }
    fclose($out);
    exit;
}

/**
 * Renders the report page.
 */
function owbn_render_hst_ast_page()
{
    if (!owbn_hst_ast_can_view()) {
        wp_die(esc_html__('You do not have permission to view this page.', 'owbn-chronicle-manager'), '', array('response' => 403));
    }

    $rows = owbn_hst_ast_report_rows();

    echo '<div class="wrap"><h1 class="wp-heading-inline">' . esc_html__('HST & AST Report', 'owbn-chronicle-manager') . '</h1>';

    if (empty($rows)) {
        echo '<hr class="wp-header-end"><p>' . esc_html__('No active chronicles on this site.', 'owbn-chronicle-manager') . '</p></div>';
        return;
    }

    $incomplete = 0;
    foreach ($rows as $row) {
        if (owbn_hst_ast_row_incomplete($row)) {
            $incomplete++;
        }
    }

    $csv_url = wp_nonce_url(admin_url('admin-post.php?action=owbn_hst_ast_csv'), 'owbn_hst_ast_csv');

    echo ' <a href="' . esc_url($csv_url) . '" class="page-title-action">' . esc_html__('Export CSV', 'owbn-chronicle-manager') . '</a>';
    echo '<hr class="wp-header-end">';

    echo '<p>' . sprintf(
        /* translators: 1: chronicle count, 2: chronicles with a missing name or email */
        esc_html__('%1$d active chronicles. %2$d have an HST or AST with no name or no email address.', 'owbn-chronicle-manager'),
        count($rows),
        $incomplete
    ) . '</p>';

    echo '<p class="owbn-hst-ast-tools">';
    echo '<label for="owbn-hst-ast-filter" class="screen-reader-text">' . esc_html__('Filter the list', 'owbn-chronicle-manager') . '</label>';
    echo '<input type="search" id="owbn-hst-ast-filter" placeholder="' . esc_attr__('Filter by chronicle, name or email', 'owbn-chronicle-manager') . '" class="regular-text"> ';
    echo '<label><input type="checkbox" id="owbn-hst-ast-incomplete"> ' . esc_html__('Only chronicles with a missing name or email', 'owbn-chronicle-manager') . '</label>';
    echo ' <span id="owbn-hst-ast-shown" class="description"></span>';
    echo '</p>';

    $columns = array(
        __('Chronicle', 'owbn-chronicle-manager'),
        __('HST (email)', 'owbn-chronicle-manager'),
        __('Staff (emails)', 'owbn-chronicle-manager'),
        __('Last Updated', 'owbn-chronicle-manager'),
    );

    echo '<table class="widefat striped" id="owbn-hst-ast-table"><thead><tr>';
    foreach ($columns as $label) {
        echo '<th scope="col" class="owbn-hst-ast-sort" tabindex="0">' . esc_html($label) . '</th>';
    }
    echo '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $edit_link = get_edit_post_link($row['chronicle_id']);
        $title     = $edit_link
            ? '<a href="' . esc_url($edit_link) . '">' . esc_html($row['chronicle']) . '</a>'
            : esc_html($row['chronicle']);

        echo '<tr data-incomplete="' . (owbn_hst_ast_row_incomplete($row) ? '1' : '0') . '">';
        echo '<td data-label="' . esc_attr($columns[0]) . '">' . $title . '<span class="owbn-hst-ast-slug">' . esc_html($row['slug']) . '</span></td>';
        echo '<td data-label="' . esc_attr($columns[1]) . '">' . owbn_hst_ast_person_html($row['hst']) . '</td>';
        echo '<td data-label="' . esc_attr($columns[2]) . '">';
        if (empty($row['staff'])) {
            echo '<span class="description">' . esc_html__('None listed', 'owbn-chronicle-manager') . '</span>';
        }
        foreach ($row['staff'] as $person) {
            echo owbn_hst_ast_person_html($person);
        }
        echo '</td>';
        echo '<td data-label="' . esc_attr($columns[3]) . '">' . esc_html($row['updated']) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    owbn_hst_ast_print_assets();

    echo '</div>';
}

/**
 * Prints the table's style and its filter and sort script.
 */
function owbn_hst_ast_print_assets()
{
    ?>
    <style>
        #owbn-hst-ast-table th.owbn-hst-ast-sort { cursor: pointer; }
        #owbn-hst-ast-table th.owbn-hst-ast-sort[data-dir="asc"]::after { content: " \25B2"; }
        #owbn-hst-ast-table th.owbn-hst-ast-sort[data-dir="desc"]::after { content: " \25BC"; }
        #owbn-hst-ast-table td { vertical-align: top; }
        .owbn-hst-ast-missing { color: #b32d2e; font-weight: 600; }
        .owbn-hst-ast-tools { margin: 12px 0; }
        #owbn-hst-ast-shown { margin-left: 12px; }
        .owbn-hst-ast-slug { display: block; color: #646970; font-size: 12px; }
        .owbn-hst-ast-person { margin-bottom: 8px; }
        .owbn-hst-ast-person:last-child { margin-bottom: 0; }
        .owbn-hst-ast-name, .owbn-hst-ast-email { display: block; }
        .owbn-hst-ast-email { color: #50575e; word-break: break-all; }
        @media (max-width: 782px) {
            #owbn-hst-ast-table thead { display: none; }
            #owbn-hst-ast-table, #owbn-hst-ast-table tbody, #owbn-hst-ast-table tr, #owbn-hst-ast-table td { display: block; width: auto; }
            #owbn-hst-ast-table tr { border: 1px solid #c3c4c7; margin-bottom: 12px; }
            #owbn-hst-ast-table td::before { content: attr(data-label); display: block; font-weight: 600; color: #50575e; margin-bottom: 2px; }
        }
    </style>
    <script>
    (function () {
        var table = document.getElementById('owbn-hst-ast-table');
        var filter = document.getElementById('owbn-hst-ast-filter');
        var incompleteOnly = document.getElementById('owbn-hst-ast-incomplete');
        var shown = document.getElementById('owbn-hst-ast-shown');
        var body = table.tBodies[0];

        function apply() {
            var needle = filter.value.trim().toLowerCase();
            var count = 0;
            Array.prototype.forEach.call(body.rows, function (row) {
                var visible = row.textContent.toLowerCase().indexOf(needle) !== -1
                    && (!incompleteOnly.checked || row.getAttribute('data-incomplete') === '1');
                row.style.display = visible ? '' : 'none';
                if (visible) { count++; }
            });
            shown.textContent = count + ' of ' + body.rows.length + ' chronicles shown';
        }

        function sortBy(index, th) {
            var dir = th.getAttribute('data-dir') === 'asc' ? 'desc' : 'asc';
            Array.prototype.forEach.call(table.tHead.rows[0].cells, function (cell) { cell.removeAttribute('data-dir'); });
            th.setAttribute('data-dir', dir);
            var rows = Array.prototype.slice.call(body.rows);
            rows.sort(function (a, b) {
                var result = a.cells[index].textContent.localeCompare(b.cells[index].textContent, undefined, { numeric: true, sensitivity: 'base' });
                return dir === 'asc' ? result : -result;
            });
            rows.forEach(function (row) { body.appendChild(row); });
        }

        Array.prototype.forEach.call(table.tHead.rows[0].cells, function (th, index) {
            th.addEventListener('click', function () { sortBy(index, th); });
            th.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); sortBy(index, th); }
            });
        });
        filter.addEventListener('input', apply);
        incompleteOnly.addEventListener('change', apply);
        apply();
    }());
    </script>
    <?php
}
