(function ($) {
    // -----------------------------
    // LOCATION BLOCKS
    // -----------------------------
    $(document).on('click', '.toggle-location', function () {
        const body = $(this).closest('.owbn-location-block').find('.owbn-location-body');
        body.slideToggle();
    });

    $(document).on('click', '.remove-location', function () {
        $(this).closest('.owbn-location-block').remove();
    });

    $(document).on('click', '.add-location', function () {
        const container = $(this).closest('.owbn-repeatable-group');
        const key = container.data('key');
        const lastIndex = container.find('.owbn-location-block').length;

        const lastBlock = container.find('.owbn-location-block').last();
        const newBlock = lastBlock.clone();

        newBlock.find('input, textarea, select').each(function () {
            if ($(this).is(':checkbox') || $(this).is(':radio')) {
                $(this).prop('checked', false);
            } else {
                $(this).val('');
            }
        });

        newBlock.find('[name], [id], [for]').each(function () {
            ['name', 'id', 'for'].forEach(attr => {
                const val = $(this).attr(attr);
                if (val) {
                    $(this).attr(attr, val.replace(/\[\d+\]/, '[' + lastIndex + ']').replace(/_\d+_/, '_' + lastIndex + '_'));
                }
            });
        });

        newBlock.find('.owbn-location-body').hide();
        $(this).before(newBlock);
        newBlock.find('.owbn-select2').select2({ width: '100%' });

        newBlock.find('textarea').each(function () {
            const id = $(this).attr('id');
            if (id && typeof wp !== 'undefined' && wp.editor && wp.editor.initialize) {
                wp.editor.initialize(id, {
                    tinymce: {
                        wpautop: true,
                        plugins: 'link',
                        toolbar1: 'bold italic link'
                    },
                    quicktags: true,
                    mediaButtons: false
                });
            }
        });
    });

    // -----------------------------
    // SESSION BLOCKS
    // -----------------------------
    $(document).on('click', '.add-session', function () {
        const container = $(this).closest('.owbn-repeatable-group');
        const template = container.find('.owbn-session-template').clone().removeClass('owbn-session-template').show();
        const lastIndex = container.find('.owbn-session-block').not('.owbn-session-template').length;

        template.find('[name], [id], [for]').each(function () {
            ['name', 'id', 'for'].forEach(attr => {
                const val = $(this).attr(attr);
                if (val) {
                    $(this).attr(attr, val.replace(/__INDEX__/g, lastIndex));
                }
            });
        });

        template.find('textarea').each(function () {
            const id = $(this).attr('id');
            if (id && tinymce.get(id)) {
                tinymce.get(id).remove();
            }
        });

        container.find('.add-session').before(template);
        template.find('.owbn-select2').select2({ width: '100%' });

        template.find('textarea').each(function () {
            const id = $(this).attr('id');
            if (id) {
                wp.editor.initialize(id, {
                    tinymce: { wpautop: true, plugins: 'link', toolbar1: 'bold italic link' },
                    quicktags: true,
                    mediaButtons: false
                });
            }
        });
    });

    $(document).on('click', '.toggle-session', function () {
        const body = $(this).closest('.owbn-session-block').find('.owbn-session-body');
        body.slideToggle();
    });

    $(document).on('click', '.remove-session', function () {
        $(this).closest('.owbn-session-block').remove();
    });

    // -----------------------------
    // DOCUMENT LINK BLOCKS
    // -----------------------------
    $(document).on('click', '.toggle-document', function () {
        const body = $(this).closest('.owbn-document-block').find('.owbn-document-body');
        body.slideToggle();
    });

    $(document).on('click', '.remove-document-link', function () {
        $(this).closest('.owbn-document-block').remove();
    });

    $(document).on('click', '.add-document-link', function () {
        const container = $(this).closest('.owbn-repeatable-group');
        const key = container.data('key');
        const template = container.find('template.owbn-document-template').html();
        const lastIndex = container.find('.owbn-document-block').length;
        const newBlockHtml = template.replace(/__INDEX__/g, lastIndex);
        const $newBlock = $(newBlockHtml);
        $newBlock.find('[disabled]').prop('disabled', false);
        $(this).before($newBlock);
    });

    // -----------------------------
    // SOCIAL LINK BLOCKS
    // -----------------------------
    $(document).on('click', '.toggle-social', function () {
        const body = $(this).closest('.owbn-social-block').find('.owbn-social-body');
        body.slideToggle();
    });

    $(document).on('click', '.remove-social-link', function () {
        $(this).closest('.owbn-social-block').remove();
    });

    $(document).on('click', '.add-social-link', function () {
        const container = $(this).closest('.owbn-repeatable-group');
        const key = container.data('key');
        const template = container.find('.owbn-social-template').html();
        const lastIndex = container.find('.owbn-social-block').length;
        const newBlockHtml = template.replace(/__INDEX__/g, lastIndex);
        const $newBlock = $(newBlockHtml);
        $newBlock.find('[disabled]').prop('disabled', false);
        container.find('.add-social-link').before($newBlock);
        $newBlock.find('.owbn-select2').select2({ width: '100%' });
    });

    // -----------------------------
    // EMAIL LIST BLOCKS
    // -----------------------------
    $(document).on('click', '.toggle-email', function () {
        const body = $(this).closest('.owbn-email-block').find('.owbn-email-body');
        body.slideToggle();
    });

    $(document).on('click', '.remove-email-list', function () {
        $(this).closest('.owbn-email-block').remove();
    });

    $(document).on('click', '.add-email-list', function () {
        const container = $(this).closest('.owbn-repeatable-group');
        const key = container.data('key');
        const template = container.find('.owbn-email-template').html();
        const lastIndex = container.find('.owbn-email-block').length;
        const newBlockHtml = template.replace(/__INDEX__/g, lastIndex);
        const $newBlock = $(newBlockHtml);
        $newBlock.find('[disabled]').prop('disabled', false);
        $(this).before($newBlock);

        const newEditorId = `${key}_${lastIndex}_description`;
        if (typeof wp !== 'undefined' && wp.editor && wp.editor.initialize) {
            wp.editor.initialize(newEditorId, {
                tinymce: {
                    wpautop: true,
                    plugins: 'link',
                    toolbar1: 'bold italic link'
                },
                quicktags: true,
                mediaButtons: false
            });
        }
    });

    // -----------------------------
    // AST BLOCKS (Chronicles)
    // -----------------------------
    $(document).on('click', '.owbn-add-ast', function () {
        const wrapper = $('#ast-group-wrapper');
        const template = wrapper.find('.owbn-ast-template').clone().removeClass('owbn-ast-template').show();
        const index = wrapper.find('.owbn-ast-block').not('.owbn-ast-template').length;

        template.find('[name]').each(function () {
            const name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace('__INDEX__', index));
            }
        });

        wrapper.find('.owbn-add-ast').before(template);
        template.find('.owbn-select2').select2({ width: '100%' });
    });

    $(document).on('click', '.owbn-remove-ast', function () {
        $(this).closest('.owbn-ast-block').remove();
    });

    // -----------------------------
    // SUBCOORD BLOCKS (Coordinators)
    // -----------------------------
    $(document).on('click', '.owbn-add-subcoord', function () {
        const wrapper = $('#subcoord-group-wrapper');
        const template = wrapper.find('.owbn-subcoord-template').clone().removeClass('owbn-subcoord-template').show();
        const index = wrapper.find('.owbn-subcoord-block').not('.owbn-subcoord-template').length;

        template.find('[name]').each(function () {
            const name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace('__INDEX__', index));
            }
        });

        wrapper.find('.owbn-add-subcoord').before(template);
        template.find('.owbn-select2').select2({ width: '100%' });
    });

    $(document).on('click', '.owbn-remove-subcoord', function () {
        $(this).closest('.owbn-subcoord-block').remove();
    });

    // -----------------------------
    // Utility: Select2 Init
    // -----------------------------
    function initializeSelect2(scope = $(document)) {
        if (!$.fn.select2) return;

        scope.find('select.owbn-select2.multi').each(function () {
            const $el = $(this);
            if (!$el.hasClass('select2-hidden-accessible')) {
                $el.select2({
                    width: '100%',
                    closeOnSelect: false,
                    placeholder: $el.find('option:first').text(),
                    allowClear: true,
                    minimumResultsForSearch: 0
                });
            }
        });

        scope.find('select.owbn-select2.single').each(function () {
            const $el = $(this);
            if (!$el.hasClass('select2-hidden-accessible')) {
                $el.select2({
                    width: '100%',
                    closeOnSelect: true,
                    placeholder: $el.find('option:first').text(),
                    allowClear: true,
                    minimumResultsForSearch: 0
                });
            }
        });

        scope.find('select.owbn-select2:not(.multi):not(.single)').each(function () {
            const $el = $(this);
            if (!$el.hasClass('select2-hidden-accessible')) {
                $el.select2({
                    width: '100%',
                    closeOnSelect: true,
                    placeholder: $el.find('option:first').text(),
                    allowClear: true,
                    minimumResultsForSearch: 0
                });
            }
        });
    }

    // -----------------------------
    // Satellite Toggle (Chronicles)
    // -----------------------------
    function toggleSatelliteDependentFields() {
        const isSatellite = $('#chronicle_satellite').is(':checked');
        if (isSatellite) {
            $('#owbn-cm-info-wrapper').hide();
            $('#owbn-cm-info-message').show();
            $('.owbn-parent-chronicle-field').show();
        } else {
            $('#owbn-cm-info-wrapper').show();
            $('#owbn-cm-info-message').hide();
            $('.owbn-parent-chronicle-field').hide();
        }
    }

    // -----------------------------
    // OWBN Chronicle List Filters
    // -----------------------------
    function applyChronicleFilters() {
        const filters = {
            country: $('#filter-country').val(),
            'chronicle-region': $('#filter-chronicle-region').val(),
            genre: $('#filter-genre').val(),
            'game-type': $('#filter-type').val(),
            probationary: $('#filter-probationary').val(),
            satellite: $('#filter-satellite').val()
        };

        $('.chron-wrapper').each(function () {
            const $row = $(this);
            let visible = true;

            $.each(filters, function (key, value) {
                if (!value) return;

                const dataAttr = $row.data(key);
                if (key === 'genre') {
                    const genres = (dataAttr || '').toString().split(' ');
                    if (!genres.includes(value)) {
                        visible = false;
                        return false;
                    }
                } else {
                    if ((dataAttr || '').toString() !== value) {
                        visible = false;
                        return false;
                    }
                }
            });

            $row.toggle(visible);
        });
    }

    function populateFilterOptions() {
        const filters = {
            '#filter-country': 'country',
            '#filter-chronicle-region': 'chronicle-region',
            '#filter-genre': 'genre',
            '#filter-type': 'game-type',
            '#filter-probationary': 'probationary',
            '#filter-satellite': 'satellite'
        };

        $.each(filters, function (selectId, dataAttr) {
            const $select = $(selectId);
            if (!$select.length) return;

            const values = new Set();

            $('.chron-wrapper').each(function () {
                const raw = $(this).data(dataAttr);
                if (!raw) return;

                if (dataAttr === 'genre') {
                    raw.toString().split(' ').forEach(g => {
                        if (g) values.add(g);
                    });
                } else {
                    values.add(raw.toString());
                }
            });

            const sorted = Array.from(values).sort();
            sorted.forEach(value => {
                let label = value;
                if (dataAttr === 'probationary' || dataAttr === 'satellite') {
                    label = value === '1' ? 'Yes' : 'No';
                }
                $select.append(`<option value="${value}">${label}</option>`);
            });
        });
    }

    // -----------------------------
    // Chronicles List Filter Block
    // -----------------------------
    function initChroniclesListFilterBlock() {
        const filterKeys = new Set();
        const filterValues = {};

        $('.chron-list-wrapper').each(function () {
            const $row = $(this);
            $.each($row.data(), function (key, val) {
                if (key === 'slug' || key === 'id') return;
                filterKeys.add(key);
                if (!filterValues[key]) filterValues[key] = new Set();

                if (key === 'genre' || key === 'genres') {
                    (val || '').toString().split(',').forEach(v => {
                        if (v.trim()) filterValues[key].add(v.trim());
                    });
                } else {
                    if (val !== '' && val !== undefined) {
                        filterValues[key].add(val.toString());
                    }
                }
            });
        });

        filterKeys.forEach(key => {
            const $select = $(`.owbn-chronicles-list-filters select[data-filter="${key}"]`);
            if (!$select.length) return;

            const sorted = Array.from(filterValues[key]).sort();
            sorted.forEach(value => {
                let label = value;
                if (key === 'probationary' || key === 'satellite') {
                    label = value === '1' ? 'Yes' : 'No';
                }

                $select.append(`<option value="${value}">${label}</option>`);
            });
        });

        $('.owbn-chronicles-list-filters select').on('change', applyChroniclesListFilters);
        $('#clear-filters').on('click', function (e) {
            e.preventDefault();
            $('.owbn-chronicles-list-filters select').val(null).trigger('change');
        });
    }

    function applyChroniclesListFilters() {
        const filterValues = {};

        $('.owbn-chronicles-list-filters select').each(function () {
            const key = $(this).data('filter');
            const val = $(this).val();
            if (key && val) {
                filterValues[key] = val;
            }
        });

        $('.chron-list-wrapper').each(function () {
            const $row = $(this);
            let visible = true;

            for (const key in filterValues) {
                const filterVal = filterValues[key];
                const rowVal = $row.data(key);

                if (key === 'genre' || key === 'genres') {
                    const genres = (rowVal || '').toString().split(',').map(v => v.trim());
                    if (!genres.includes(filterVal)) {
                        visible = false;
                        break;
                    }
                } else {
                    if ((rowVal || '').toString() !== filterVal) {
                        visible = false;
                        break;
                    }
                }
            }

            $row.toggle(visible);
        });
    }

    // -----------------------------
    // Init
    // -----------------------------
    $(document).ready(function () {
        initializeSelect2();
        toggleSatelliteDependentFields();
        $('#chronicle_satellite').on('change', toggleSatelliteDependentFields);

        // Browser validation fix for hidden required inputs
        $('form').on('submit', function () {
            $(this).find(':input[required]').each(function () {
                if (!this.offsetParent) {
                    $(this).closest('.owbn-document-body, .owbn-email-body, .owbn-social-body, .owbn-location-body, .owbn-session-body')
                        .show();
                }
            });
        });

        // Chronicle Filters Init
        if ($('.owbn-chronicle-filters').length) {
            populateFilterOptions();
            $('.owbn-chronicle-filters select').on('change', applyChronicleFilters);

            $('#clear-filters').on('click', function (e) {
                e.preventDefault();
                $('.owbn-chronicle-filters select').val(null).trigger('change');
            });
        }

        // New Chronicles List Filters Init
        if ($('.owbn-chronicles-list-filters').length) {
            initChroniclesListFilterBlock();
        }
    });
})(jQuery);