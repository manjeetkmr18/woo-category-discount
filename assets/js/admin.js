/**
 * Admin JavaScript for Category Discount
 *
 * @package WC_Category_Discount
 * @since   1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize category search
        initializeCategorySearch();
        
        // Initialize discount type switching
        initializeDiscountTypeSwitching();
        
        // Initialize bulk actions
        initializeBulkActions();
        
        // Initialize apply to children functionality
        initializeApplyToChildren();
        
        // Initialize remove discount functionality
        initializeRemoveDiscount();
    });

    /**
     * Initialize category search with autocomplete
     */
    function initializeCategorySearch() {
        $('#category-search').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: wcCategoryDiscount.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wc_category_discount_search',
                        search: request.term,
                        nonce: wcCategoryDiscount.nonce
                    },
                    success: function(data) {
                        if (data.success) {
                            response($.map(data.data, function(item) {
                                return {
                                    label: item.name + (item.parent ? ' (in ' + item.parent + ')' : '') + ' - ' + item.count + ' products',
                                    value: item.name,
                                    id: item.id,
                                    name: item.name,
                                    parent: item.parent,
                                    count: item.count
                                };
                            }));
                        }
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                // Scroll to the selected category row
                var categoryRow = $('.category-row[data-category-id="' + ui.item.id + '"]');
                if (categoryRow.length) {
                    $('html, body').animate({
                        scrollTop: categoryRow.offset().top - 100
                    }, 500);
                    categoryRow.addClass('highlight');
                    setTimeout(function() {
                        categoryRow.removeClass('highlight');
                    }, 2000);
                }
                return false;
            }
        });
    }

    /**
     * Initialize discount type switching
     */
    function initializeDiscountTypeSwitching() {
        $(document).on('change', '.discount-type', function() {
            var $this = $(this);
            var $row = $this.closest('tr');
            var $valueInput = $row.find('.discount-value');
            var $symbol = $row.find('.percentage-symbol');
            
            if ($this.val() === 'percentage') {
                $valueInput.attr('max', '100');
                $symbol.text('%');
            } else {
                $valueInput.attr('max', '9999');
                $symbol.text(wcCategoryDiscount.currencySymbol);
            }
        });
    }

    /**
     * Initialize bulk actions
     */
    function initializeBulkActions() {
        // Add checkboxes for bulk selection
        $('.wc-category-discount-table thead tr').prepend('<th class="column-select"><input type="checkbox" id="select-all" /></th>');
        $('.wc-category-discount-table tbody tr').each(function() {
            var categoryId = $(this).data('category-id');
            if (categoryId) {
                $(this).prepend('<td class="column-select"><input type="checkbox" class="category-select" value="' + categoryId + '" /></td>');
            } else {
                $(this).prepend('<td class="column-select"></td>');
            }
        });

        // Select all functionality
        $('#select-all').on('change', function() {
            $('.category-select').prop('checked', this.checked);
        });

        // Update select all when individual checkboxes change
        $(document).on('change', '.category-select', function() {
            var allChecked = $('.category-select:checked').length === $('.category-select').length;
            $('#select-all').prop('checked', allChecked);
        });

        // Bulk discount type change
        $('#bulk-discount-type').on('change', function() {
            var $valueInput = $('#bulk-discount-value');
            if ($(this).val() === 'percentage') {
                $valueInput.attr('max', '100');
            } else {
                $valueInput.attr('max', '9999');
            }
        });

        // Apply bulk discount
        $('#apply-bulk-discount').on('click', function() {
            var selectedCategories = [];
            $('.category-select:checked').each(function() {
                selectedCategories.push($(this).val());
            });

            if (selectedCategories.length === 0) {
                alert('Please select at least one category.');
                return;
            }

            var discountType = $('#bulk-discount-type').val();
            var discountValue = parseFloat($('#bulk-discount-value').val());
            var applyToChildren = $('#bulk-apply-children').is(':checked');

            if (!discountValue || discountValue <= 0) {
                alert('Please enter a valid discount value.');
                return;
            }

            // Apply discount to selected categories
            selectedCategories.forEach(function(categoryId) {
                var $row = $('.category-row[data-category-id="' + categoryId + '"]');
                $row.find('.discount-type').val(discountType);
                $row.find('.discount-value').val(discountValue);
                $row.find('.apply-to-children').prop('checked', applyToChildren);
                
                // Trigger change event to update UI
                $row.find('.discount-type').trigger('change');
                $row.addClass('has-discount');
            });

            // Clear bulk form
            $('#bulk-discount-value').val('');
            $('#bulk-apply-children').prop('checked', false);
            $('.category-select').prop('checked', false);
            $('#select-all').prop('checked', false);

            showNotice('Bulk discount applied. Don\'t forget to save your changes!', 'info');
        });
    }

    /**
     * Initialize apply to children functionality
     */
    function initializeApplyToChildren() {
        $(document).on('click', '.apply-to-children-btn', function() {
            var $this = $(this);
            var $row = $this.closest('tr');
            var categoryId = $this.data('category-id');
            var discountType = $row.find('.discount-type').val();
            var discountValue = parseFloat($row.find('.discount-value').val());

            if (!discountValue || discountValue <= 0) {
                alert('Please enter a valid discount value first.');
                return;
            }

            if (!confirm('This will apply the same discount to all child categories. Continue?')) {
                return;
            }

            $this.prop('disabled', true).text('Applying...');

            $.ajax({
                url: wcCategoryDiscount.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_category_discount_apply_to_children',
                    category_id: categoryId,
                    discount_type: discountType,
                    discount_value: discountValue,
                    nonce: wcCategoryDiscount.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(response.data, 'success');
                        // Reload page to show updated discounts
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotice(response.data || 'An error occurred.', 'error');
                    }
                },
                error: function() {
                    showNotice('An error occurred while applying discounts.', 'error');
                },
                complete: function() {
                    $this.prop('disabled', false).text('Apply to Children');
                }
            });
        });
    }

    /**
     * Initialize remove discount functionality
     */
    function initializeRemoveDiscount() {
        $(document).on('click', '.remove-discount', function() {
            var $row = $(this).closest('tr');
            
            if (!confirm('Are you sure you want to remove this discount?')) {
                return;
            }

            // Clear the discount values
            $row.find('.discount-value').val('0');
            $row.find('.apply-to-children').prop('checked', false);
            $row.removeClass('has-discount');

            showNotice('Discount removed. Don\'t forget to save your changes!', 'info');
        });
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        var noticeClass = 'notice-' + (type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info'));
        var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wc-category-discount-wrap h1').after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

})(jQuery);