/*!
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2013, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license1.0.html Craft License
 * @link      http://buildwithcraft.com
 */

(function($) {


var CP = Garnish.Base.extend({

	$alerts: null,
	$header: null,
	$nav: null,

	$overflowNavMenuItem: null,
	$overflowNavMenuBtn: null,
	$overflowNavMenu: null,
	$overflowNavMenuList: null,


	$notificationWrapper: null,
	$notificationContainer: null,
	$main: null,
	$sidebar: null,
	$sidebarNav: null,
	$sidebarNavPlaceholder: null,
	$altSidebar: null,
	$altSidebarNavBtn: null,
	$altSidebarNavMenu: null,
	$content: null,
	$collapsibleTables: null,

	navItems: null,
	totalNavItems: null,
	visibleNavItems: null,
	totalNavWidth: null,
	showingOverflowNavMenu: false,
	showingSidebar: true,

	fixedNotifications: false,
	fixedSidebarNav: false,

	tabs: null,
	selectedTab: null,

	init: function()
	{
		// Find all the key elements
		this.$alerts = $('#alerts');
		this.$header = $('#header');
		this.$nav = $('#nav');
		this.$notificationWrapper = $('#notifications-wrapper');
		this.$notificationContainer = $('#notifications');
		this.$main = $('#main');
		this.$sidebar = $('#sidebar');
		this.$sidebarNav = this.$sidebar.children('nav');
		this.$content = $('#content');
		this.$collapsibleTables = this.$content.find('table.collapsible');

		// Find all the nav items
		this.navItems = [];
		this.totalNavWidth = CP.baseNavWidth;

		var $navItems = this.$nav.children();
		this.totalNavItems = $navItems.length;
		this.visibleNavItems = this.totalNavItems;

		for (var i = 0; i < this.totalNavItems; i++)
		{
			var $li = $($navItems[i]),
				width = $li.width();

			this.navItems.push($li);
			this.totalNavWidth += width;
		}

		this.addListener(Garnish.$win, 'resize', 'onWindowResize');
		this.onWindowResize();

		this.addListener(Garnish.$win, 'scroll', 'onWindowScroll');
		this.onWindowScroll();

		// Fade the notification out in two seconds
		var $notifications = this.$notificationContainer.children();
		$notifications.delay(CP.notificationDuration).fadeOut();


		// Tabs
		this.tabs = {};
		var $tabs = $('#tabs a');

		// Find the tabs that link to a div on the page
		for (var i = 0; i < $tabs.length; i++)
		{
			var $tab = $($tabs[i]),
				href = $tab.attr('href');

			if (href && href.charAt(0) == '#')
			{
				this.tabs[href] = {
					$tab: $tab,
					$target: $(href)
				};

				this.addListener($tab, 'activate', 'selectTab');
			}

			if (!this.selectedTab && $tab.hasClass('sel'))
			{
				this.selectedTab = href;
			}
		}

		if (document.location.hash && typeof this.tabs[document.location.hash] != 'undefined')
		{
			this.tabs[document.location.hash].$tab.trigger('click');
		}

		// Secondary form submit buttons
		this.addListener($('.formsubmit'), 'activate', function(ev)
		{
			var $btn = $(ev.currentTarget);

			// Is this a menu item?
			if ($btn.data('menu'))
			{
				var $form = $btn.data('menu').$btn.closest('form');
			}
			else
			{
				var $form = $btn.closest('form');
			}

			if ($btn.attr('data-action'))
			{
				$('<input type="hidden" name="action" value="'+$btn.attr('data-action')+'"/>').appendTo($form);
			}

			if ($btn.attr('data-redirect'))
			{
				$('<input type="hidden" name="redirect" value="'+$btn.attr('data-redirect')+'"/>').appendTo($form);
			}

			$form.submit();
		});

		// Alerts
		if (this.$alerts.length)
		{
			this.initAlerts();
		}
	},

	/**
	 * Handles stuff that should happen when the window is resized.
	 */
	onWindowResize: function()
	{
		// Get the new window width
		this.onWindowResize._cpWidth = Math.min(Garnish.$win.width(), CP.maxWidth);

		// Update the responsive nav
		this.updateResponsiveNav();

		// Update the responsive sidebar
		this.updateResponsiveSidebar();

		// Update any responsive tables
		this.updateResponsiveTables();
	},

	updateResponsiveNav: function()
	{
		// Is an overflow menu going to be needed?
		if (this.onWindowResize._cpWidth < this.totalNavWidth)
		{
			// Show the overflow menu button
			if (!this.showingOverflowNavMenu)
			{
				if (!this.$overflowNavMenuBtn)
				{
					// Create it
					this.$overflowNavMenuItem = $('<li/>').appendTo(this.$nav);
					this.$overflowNavMenuBtn = $('<a class="menubtn" title="'+Craft.t('More')+'">…</a>').appendTo(this.$overflowNavMenuItem);
					this.$overflowNavMenu = $('<div id="overflow-nav" class="menu" data-align="right"/>').appendTo(this.$overflowNavMenuItem);
					this.$overflowNavMenuList = $('<ul/>').appendTo(this.$overflowNavMenu);
					new Garnish.MenuBtn(this.$overflowNavMenuBtn);
				}
				else
				{
					this.$overflowNavMenuItem.show();
				}

				this.showingOverflowNavMenu = true;
			}

			// Is the nav too tall?
			if (this.$nav.height() > CP.navHeight)
			{
				// Move items to the overflow menu until the nav is back to its normal height
				do
				{
					this.addLastVisibleNavItemToOverflowMenu();
				}
				while ((this.$nav.height() > CP.navHeight) && (this.visibleNavItems > 0));
			}
			else
			{
				// See if we can fit any more nav items in the main menu
				do
				{
					this.addFirstOverflowNavItemToMainMenu();
				}
				while ((this.$nav.height() == CP.navHeight) && (this.visibleNavItems < this.totalNavItems));

				// Now kick the last one back.
				this.addLastVisibleNavItemToOverflowMenu();
			}
		}
		else
		{
			if (this.showingOverflowNavMenu)
			{
				// Hide the overflow menu button
				this.$overflowNavMenuItem.hide();

				// Move any nav items in the overflow menu back to the main nav menu
				while (this.visibleNavItems < this.totalNavItems)
				{
					this.addFirstOverflowNavItemToMainMenu();
				}

				this.showingOverflowNavMenu = false;
			}
		}
	},

	/**
	 * Updates the responsive sidebar
	 */
	updateResponsiveSidebar: function()
	{
		if (!this.$sidebar.length)
		{
			return;
		}

		if (this.onWindowResize._cpWidth < CP.minSidebarWidth)
		{
			if (this.showingSidebar)
			{
				this.makeSidebarNavUnfixed();
				this.$main.removeClass(CP.hasSidebarClass);

				if (!this.$altSidebar)
				{
					this.$altSidebar = $('<div id="sidebar-alt"/>').insertAfter(this.$sidebar);

					var $sidebarChildren = this.$sidebar.children();

					for (var i = 0; i < $sidebarChildren.length; i++)
					{
						var $elem = $($sidebarChildren[i]).clone(true);

						if ($elem.prop('nodeName') == 'NAV')
						{
							// Create a menu instead
							var selectedText = $elem.find('.sel:first').text(),
								$list = $elem.children();

							this.$altSidebarNavBtn = $('<div class="btn menubtn">'+selectedText+'</div>').appendTo(this.$altSidebar);
							this.$altSidebarNavMenu = $('<div class="menu menulist"/>').appendTo(this.$altSidebar);

							$list.appendTo(this.$altSidebarNavMenu);
							new Garnish.MenuBtn(this.$altSidebarNavBtn);
							$elem.detach();
						}
						else
						{
							$elem.appendTo(this.$altSidebar);
						}
					}
				}
				else
				{
					this.$altSidebar.show();
				}

				this.$sidebar.hide();
				this.showingSidebar = false;
			}
		}
		else
		{
			if (!this.showingSidebar)
			{
				this.$main.addClass(CP.hasSidebarClass);
				this.$altSidebar.hide();
				this.$sidebar.show();
				this.showingSidebar = true;
				this.updateFixedSidebarNav();
			}
		}
	},

	updateResponsiveTables: function()
	{
		this.updateResponsiveTables._contentWidth = this.$content.width();

		for (this.updateResponsiveTables._i = 0; this.updateResponsiveTables._i < this.$collapsibleTables.length; this.updateResponsiveTables._i++)
		{
			this.updateResponsiveTables._$table = $(this.$collapsibleTables[this.updateResponsiveTables._i]);
			this.updateResponsiveTables._check = false;

			if (typeof this.updateResponsiveTables._lastContentWidth != 'undefined')
			{
				this.updateResponsiveTables._isLinear = this.updateResponsiveTables._$table.hasClass('collapsed');

				// Getting wider?
				if (this.updateResponsiveTables._contentWidth > this.updateResponsiveTables._lastContentWidth)
				{
					if (this.updateResponsiveTables._isLinear)
					{
						this.updateResponsiveTables._$table.removeClass('collapsed');
						this.updateResponsiveTables._check = true;
					}
				}
				else
				{
					if (!this.updateResponsiveTables._isLinear)
					{
						this.updateResponsiveTables._check = true;
					}
				}
			}
			else
			{
				this.updateResponsiveTables._check = true;
			}

			if (this.updateResponsiveTables._check)
			{
				if (this.updateResponsiveTables._$table.width() > this.updateResponsiveTables._contentWidth)
				{
					this.updateResponsiveTables._$table.addClass('collapsed');
				}
			}
		}

		this.updateResponsiveTables._lastContentWidth = this.updateResponsiveTables._contentWidth;
	},

	/**
	 * Adds the last visible nav item to the overflow menu.
	 */
	addLastVisibleNavItemToOverflowMenu: function()
	{
		this.navItems[this.visibleNavItems-1].prependTo(this.$overflowNavMenuList);
		this.visibleNavItems--;
	},

	/**
	 * Adds the first overflow nav item back to the main nav menu.
	 */
	addFirstOverflowNavItemToMainMenu: function()
	{
		this.navItems[this.visibleNavItems].insertBefore(this.$overflowNavMenuItem);
		this.visibleNavItems++;
	},


	/**
	 * Handle stuff that should happen when the window scrolls.
	 */
	onWindowScroll: function()
	{
		this.onWindowScroll._scrollTop = Garnish.$win.scrollTop();

		this.updateFixedNotifications();
		this.updateFixedSidebarNav();
	},

	updateFixedNotifications: function()
	{
		this.onWindowScroll._headerHeight = this.$header.height();

		if (this.onWindowScroll._scrollTop > this.onWindowScroll._headerHeight)
		{
			if (!this.fixedNotifications)
			{
				this.$notificationWrapper.addClass('fixed');
				this.fixedNotifications = true;
			}
		}
		else
		{
			if (this.fixedNotifications)
			{
				this.$notificationWrapper.removeClass('fixed');
				this.fixedNotifications = false;
			}
		}
	},

	updateFixedSidebarNav: function()
	{
		if (this.showingSidebar && this.$sidebarNav.length)
		{
			if (this.fixedSidebarNav)
			{
				this.onWindowScroll._$offsetTarget = this.$sidebarNavPlaceholder;
			}
			else
			{
				this.onWindowScroll._$offsetTarget = this.$sidebarNav;
			}

			if (this.onWindowScroll._scrollTop > this.onWindowScroll._$offsetTarget.offset().top)
			{
				this.makeSidebarNavFixed();

				// Make sure that the nav doesn't bleed into the page footer
				this.onWindowScroll._maxNavHeight = this.$main.offset().top + this.$main.outerHeight() - Garnish.$win.scrollTop();
				this.$sidebarNav.css('max-height', this.onWindowScroll._maxNavHeight);
			}
			else
			{
				this.makeSidebarNavUnfixed();
			}
		}
	},

	makeSidebarNavFixed: function()
	{
		if (!this.fixedSidebarNav)
		{
			if (typeof $sidebarNavPlaceholder == 'undefined')
			{
				this.$sidebarNavPlaceholder = $('<div/>');
			}

			this.$sidebarNavPlaceholder.insertBefore(this.$sidebarNav);
			this.$sidebarNav.addClass('fixed');
			this.fixedSidebarNav = true;
		}
	},

	makeSidebarNavUnfixed: function()
	{
		if (this.fixedSidebarNav)
		{
			this.$sidebarNavPlaceholder.remove();
			this.$sidebarNav.removeClass('fixed');
			this.fixedSidebarNav = false;
		}
	},

	/**
	 * Dispays a notification.
	 *
	 * @param string type
	 * @param string message
	 */
	displayNotification: function(type, message)
	{
		$('<div class="notification '+type+'">'+message+'</div>')
			.appendTo(this.$notificationContainer)
			.fadeIn('fast')
			.delay(CP.notificationDuration)
			.fadeOut();
	},

	/**
	 * Displays a notice.
	 *
	 * @param string message
	 */
	displayNotice: function(message)
	{
		this.displayNotification('notice', message);
	},

	/**
	 * Displays an error.
	 *
	 * @param string message
	 */
	displayError: function(message)
	{
		this.displayNotification('error', message);
	},

	/**
	 * Select a tab
	 */
	selectTab: function(ev)
	{
		if (!this.selectedTab || ev.currentTarget != this.tabs[this.selectedTab].$tab[0])
		{
			// Hide the selected tab
			if (this.selectedTab)
			{
				this.tabs[this.selectedTab].$tab.removeClass('sel');
				this.tabs[this.selectedTab].$target.addClass('hidden');
			}

			var $tab = $(ev.currentTarget).addClass('sel');
			this.selectedTab = $tab.attr('href');
			this.tabs[this.selectedTab].$target.removeClass('hidden');
		}
	},

	fetchAlerts: function()
	{
		var data = {
			path: Craft.path
		};

		Craft.postActionRequest('app/getCpAlerts', data, $.proxy(this, 'displayAlerts'));
	},

	displayAlerts: function(alerts)
	{
		if (Garnish.isArray(alerts) && alerts.length)
		{
			this.$alerts = $('<ul id="alerts"/>').insertBefore($('#header'));

			for (var i = 0; i < alerts.length; i++)
			{
				$('<li>'+alerts[i]+'</li>').appendTo(this.$alerts);
			}

			var height = this.$alerts.height();

			this.$alerts.height(0).animate({ height: height }, 'fast', $.proxy(function()
			{
				this.$alerts.height('auto');
			}, this));

			this.initAlerts();
		}
	},

	initAlerts: function()
	{
		// Is there a domain mismatch?
		var $transferDomainLink = this.$alerts.find('.domain-mismatch:first');

		if ($transferDomainLink.length)
		{
			this.addListener($transferDomainLink, 'click', $.proxy(function(ev)
			{
				ev.preventDefault();

				if (confirm(Craft.t('Are you sure you want to transfer your license to this domain?')))
				{
					Craft.postActionRequest('app/transferLicenseToCurrentDomain', $.proxy(function(response)
					{
						if (response.success)
						{
							$transferDomainLink.parent().remove();
							this.displayNotice(Craft.t('License transferred.'));
						}
						else
						{
							if (response.error)
							{
								var error = response.error;
							}
							else
							{
								var error = 'An unknown error occurred.';
							}

							alert(error);
						}
					}, this));
				}
			}, this));
		}
	}
},
{
	maxWidth: 1051, //1024,
	navHeight: 38,
	baseNavWidth: 30,
	minSidebarWidth: 768,
	hasSidebarClass: 'has-sidebar',
	notificationDuration: 2000
});


Craft.cp = new CP();


})(jQuery);
