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

        // Clear input/select/textarea values
        newBlock.find('input, textarea, select').each(function () {
            if ($(this).is(':checkbox') || $(this).is(':radio')) {
                $(this).prop('checked', false);
            } else {
                $(this).val('');
            }
        });

        // Update attributes for new index
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

        // Init WYSIWYG editors inside the new block
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

        // Remove TinyMCE if cloned
        template.find('textarea').each(function () {
            const id = $(this).attr('id');
            if (id && tinymce.get(id)) {
                tinymce.get(id).remove();
            }
        });

        container.find('.add-session').before(template);
        template.find('.owbn-select2').select2({ width: '100%' });

        // Init WP Editor
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
    // AST BLOCKS
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
    // Utility: Select2 Init
    // -----------------------------
    function initializeSelect2(scope = $(document)) {
        if (!$.fn.select2) return;

        // MULTI-select
        scope.find('select.owbn-select2.multi').each(function () {
            const $el = $(this);
            if (!$el.hasClass('select2-hidden-accessible')) {
                $el.select2({
                    width: '100%',
                    closeOnSelect: false,
                    placeholder: 'Select one or more',
                    allowClear: true,
                    minimumResultsForSearch: 0
                });
            }
        });

        // SINGLE-select
        scope.find('select.owbn-select2.single').each(function () {
            const $el = $(this);
            if (!$el.hasClass('select2-hidden-accessible')) {
                $el.select2({
                    width: '100%',
                    closeOnSelect: true,
                    placeholder: 'Select one',
                    allowClear: true,
                    minimumResultsForSearch: 0
                });
            }
        });

        // CATCH-ALL fallback (if no multi/single defined)
        scope.find('select.owbn-select2:not(.multi):not(.single)').each(function () {
            const $el = $(this);
            if (!$el.hasClass('select2-hidden-accessible')) {
                $el.select2({
                    width: '100%',
                    closeOnSelect: true,
                    allowClear: true,
                    minimumResultsForSearch: 0
                });
            }
        });
    }

    // -----------------------------
    // Satellite Toggle Logic
    // -----------------------------
    function toggleSatelliteDependentFields() {
        const isSatellite = $('#chronicle_satellite').is(':checked');
        $('#owbn-cm-info-message').toggle(isSatellite);
        $('#owbn-cm-info-wrapper').toggle(!isSatellite);
        $('#owbn-parent-chronicle-select').toggle(isSatellite);
        $('#owbn-parent-chronicle-message').toggle(!isSatellite);
    }

    // -----------------------------
    // Init
    // -----------------------------
    $(document).ready(function () {
        initializeSelect2();
        toggleSatelliteDependentFields();
        $('#chronicle_satellite').on('change', toggleSatelliteDependentFields);
    });
})(jQuery);