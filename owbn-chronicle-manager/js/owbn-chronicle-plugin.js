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

        // ---- Browser validation fix for hidden required inputs ----
        $('form').on('submit', function () {
            // Show all hidden containers that include required inputs so browser validation doesn't choke
            $(this).find(':input[required]').each(function () {
                if (!this.offsetParent) {
                    // Traverse up to find a collapsible wrapper and show it
                    $(this).closest('.owbn-document-body, .owbn-email-body, .owbn-social-body, .owbn-location-body, .owbn-session-body')
                        .show();
                }
            });
        });
    });
})(jQuery);