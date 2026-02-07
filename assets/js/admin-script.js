/**
 * Role Based Content Hider - Admin JavaScript
 *
 * @package RBContentHider
 */

/* global jQuery, rbchAdmin */

(function ($) {
	'use strict';

	/**
	 * Inizializza le funzionalità admin del plugin.
	 */
	var RBCHAdmin = {

		/**
		 * Inizializzazione.
		 */
		init: function () {
			this.bindMenuEvents();
			this.bindWidgetEvents();
			this.bindTabEvents();
		},

		/**
		 * Gestisce gli eventi per i menu items.
		 */
		bindMenuEvents: function () {
			$(document).on('change', '.rbch-roles-checkboxes input[type="checkbox"]', function () {
				var $fieldset = $(this).closest('.rbch-menu-fieldset');
				var checked = $fieldset.find('input:checked').length;

				if (checked > 0) {
					$fieldset.css('border-left', '3px solid #d63638');
				} else {
					$fieldset.css('border-left', '');
				}
			});

			// Evidenzia i fieldset con ruoli già selezionati al caricamento.
			$('.rbch-menu-fieldset').each(function () {
				var checked = $(this).find('input:checked').length;
				if (checked > 0) {
					$(this).css('border-left', '3px solid #d63638');
				}
			});
		},

		/**
		 * Gestisce gli eventi per i widget.
		 */
		bindWidgetEvents: function () {
			$(document).on('change', '.rbch-widget-roles input[type="checkbox"]', function () {
				var $container = $(this).closest('.rbch-widget-visibility');
				var checked = $container.find('input:checked').length;

				if (checked > 0) {
					$container.find('p strong').css('color', '#d63638');
				} else {
					$container.find('p strong').css('color', '');
				}
			});
		},

		/**
		 * Gestisce la navigazione dei tab nella pagina impostazioni.
		 */
		bindTabEvents: function () {
			$('.rbch-tabs .nav-tab').on('click', function () {
				$('.rbch-tabs .nav-tab').removeClass('nav-tab-active');
				$(this).addClass('nav-tab-active');
			});
		}
	};

	$(document).ready(function () {
		RBCHAdmin.init();
	});

})(jQuery);
