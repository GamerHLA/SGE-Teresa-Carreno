(function () {
	"use strict";

	var treeviewMenu = $('.app-menu');

	// Toggle Sidebar
	$('[data-toggle="sidebar"]').click(function (event) {
		event.preventDefault();
		$('.app').toggleClass('sidenav-toggled');

		// Force resize after transition
		setTimeout(function () {
			// Trigger window resize event
			$(window).trigger('resize');

			// Force DataTables to recalculate column widths
			if ($.fn.DataTable) {
				// Reset width to force recalculation based on new container size
				$('table.dataTable').css('width', '100%');
				$.fn.DataTable.tables({ visible: true, api: true }).columns.adjust().draw(false);
			}
		}, 500);
	});

	// Activate sidebar treeview toggle
	$("[data-toggle='treeview']").click(function (event) {
		event.preventDefault();
		if (!$(this).parent().hasClass('is-expanded')) {
			treeviewMenu.find("[data-toggle='treeview']").parent().removeClass('is-expanded');
		}
		$(this).parent().toggleClass('is-expanded');
	});

	// Set initial active toggle
	$("[data-toggle='treeview.'].is-expanded").parent().toggleClass('is-expanded');

	//Activate bootstrip tooltips
	$("[data-toggle='tooltip']").tooltip();

})();
