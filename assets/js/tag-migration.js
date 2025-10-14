/**
 * Tag Migration Tool
 * Handles select-all checkbox functionality
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        var selectAll = document.getElementById('select-all-tags');

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                var checkboxes = document.querySelectorAll('input[name="tag_ids[]"]');
                for (var i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].checked = this.checked;
                }
            });
        }
    });

})();
