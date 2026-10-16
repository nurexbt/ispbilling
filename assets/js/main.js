$(document).ready(function() {
    // Set global DataTables defaults for the portal
    $.extend(true, $.fn.dataTable.defaults, {
        "dom": '<"table-responsive"t><"d-flex justify-content-between align-items-center p-3"i<"d-flex align-items-center"<"d-none"p><"custom-paging-container d-flex align-items-center gap-3">>>',
        "paging": true,
        "ordering": false,
        "info": true,
        "pageLength": 10,
        "drawCallback": function(settings) {
            var api = new $.fn.dataTable.Api(settings);
            var info = api.page.info();
            var pages = info.pages || 1;
            var curr = info.page;
            
            var $wrapper = $(settings.nTableWrapper);
            var $container = $wrapper.find('.custom-paging-container');
            
            $container.empty();
            
            if (pages <= 0) return;
            
            // 1. Prev Button
            var $prev = $('<button class="btn-circular ' + (curr === 0 ? 'disabled' : '') + '"><i class="bi bi-chevron-left"></i></button>');
            $prev.on('click', function(e) { e.preventDefault(); if(curr > 0) api.page('previous').draw('page'); });
            $container.append($prev);
            
            // 2. Numbers Pill
            var $pill = $('<div class="numbers-pill d-flex align-items-center"></div>');
            var addPageBtn = function(i) {
                var $btn = $('<button class="page-num ' + (i === curr ? 'active' : '') + '">' + (i + 1) + '</button>');
                $btn.on('click', function(e) { e.preventDefault(); api.page(i).draw('page'); });
                $pill.append($btn);
            };
            
            for (var i = 0; i < pages; i++) {
                if (i === 0 || i === pages - 1 || Math.abs(i - curr) <= 1) {
                    addPageBtn(i);
                } else if (i === 1 && curr > 2) {
                    $pill.append('<span class="page-dots">...</span>');
                } else if (i === pages - 2 && curr < pages - 3) {
                    $pill.append('<span class="page-dots">...</span>');
                }
            }
            $container.append($pill);
            
            // 3. Next Button
            var $next = $('<button class="btn-circular ' + (curr === pages - 1 ? 'disabled' : '') + '"><i class="bi bi-chevron-right"></i></button>');
            $next.on('click', function(e) { e.preventDefault(); if(curr < pages - 1) api.page('next').draw('page'); });
            $container.append($next);
        }
    });

    // Initialize generic dataTables if they exist
    if ($('#dataTable').length) {
        var defaultTable = $('#dataTable').DataTable();
        
        // Bind external search if exists
        $('.toolbar-input').on('keyup', function() {
            defaultTable.search(this.value).draw();
        });
    }

    // Live Header Clock Update
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        $('#header-time').text(`${hours}:${minutes}:${seconds}`);
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Sidebar Toggle (Mobile)
    $('#sidebarToggle').on('click', function() {
        $('#sidebar').css('transform', 'translateX(0)');
    });

    $('#sidebarClose').on('click', function() {
        $('#sidebar').css('transform', 'translateX(-100%)');
    });

    // Close sidebar when clicking outside (Mobile)
    $(document).on('click', function(e) {
        if ($(window).width() < 768) {
            if (!$(e.target).closest('#sidebar').length && !$(e.target).closest('#sidebarToggle').length) {
                $('#sidebar').css('transform', 'translateX(-100%)');
            }
        }
    });

    // Handle responsive sidebar on window resize
    $(window).resize(function() {
        if ($(window).width() >= 768) {
            $('#sidebar').css('transform', 'translateX(0)');
        } else {
            $('#sidebar').css('transform', 'translateX(-100%)');
        }
    });

    // Initial check for mobile
    if ($(window).width() < 768) {
        $('#sidebar').css('transform', 'translateX(-100%)');
    }
});
