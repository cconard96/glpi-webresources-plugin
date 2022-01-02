window.GlpiPluginWebResources = {
   plugin_root_url: CFG_GLPI.root_doc + '/' + GLPI_PLUGINS_PATH.webresources,
   manageUrl: (context, view_mode) => {
      // if current base URL doesn't contain '/front/central.php'
      if (window.location.href.indexOf('/front/central.php') === -1) {
         const updateURLParameter = function(url, param, paramVal){
            let newAdditionalURL = "";
            let tempArray = url.split("?");
            let baseURL = tempArray[0];
            let additionalURL = tempArray[1];
            let temp = "";
            if (additionalURL) {
               tempArray = additionalURL.split("&");
               for (let i=0; i<tempArray.length; i++){
                  if(tempArray[i].split('=')[0] != param){
                     newAdditionalURL += temp + tempArray[i];
                     temp = "&";
                  }
               }
            }
            const rows_txt = temp + "" + param + "=" + paramVal;
            return baseURL + "?" + newAdditionalURL + rows_txt;
         }
         window.history.replaceState('', '', updateURLParameter(window.location.href, "context", context));
         window.history.replaceState('', '', updateURLParameter(window.location.href, "view_mode", view_mode));
      } else {
         window.localStorage.setItem('webresources_central_state', JSON.stringify({
            context: context,
            view_mode: view_mode
         }));
      }
   },
   refreshDashboard: (context, view_mode) => {
      const dashboard = $('.webresources-dashboard');
      const view_switcher = $('.webresources-view-switcher');
      // if context is not defined, replace context and view_mode with the ones from getDashboardState()
      if (typeof context === 'undefined') {
         const state = GlpiPluginWebResources.getDashboardState();
         context = state.context;
         view_mode = state.view_mode;
      }

      if (context.length === 0) {
         context = 'personal';
      }

      $.ajax({
         url: (window.GlpiPluginWebResources.plugin_root_url+"/ajax/refreshDashboard.php"),
         data: {
            context: context,
            view_mode: view_mode
         }
      }).success(function(data) {
         $("#webresources-content").empty();
         $("#webresources-content").append(data);

         // Update data attributes
         dashboard.data('context', context);
         dashboard.data('view-mode', view_mode);

         // Update context dropdown value
         $('.webresources-toolbar select[name="context"]').val(context).trigger('change.select2');

         // Update view mode toggle button icon
         if (view_mode === 'grid') {
            view_switcher.find('i').attr('class', 'fas fa-list view-switcher');
            view_switcher.find('i').attr('title', 'List view');
         } else {
            view_switcher.find('i').attr('class', 'fas fa-th view-switcher');
            view_switcher.find('i').attr('title', 'Grid view');
         }
         window.GlpiPluginWebResources.manageUrl(context, view_mode);
         window.GlpiPluginWebResources.applySearchFilters($('.webresources-toolbar input[name="search"]').get(0));
      });
   },
   getDashboardState: () => {
      if (window.location.href.indexOf('/front/central.php') === -1) {
         const queryParams = new URLSearchParams(window.location.search);
         return {
            context: queryParams.get('context') || 'personal',
            view_mode: queryParams.get('view_mode') || 'grid'
         }
      } else {
         const ls_state = window.localStorage.getItem('webresources_central_state');
         let state = {
            context: 'personal',
            view_mode: 'grid'
         };
         if (ls_state) {
            return JSON.parse(ls_state);
         } else {
            return state;
         }
      }
   },
   registerListeners: () => {
      const page = $('#page');

      page.on('change', '.webresources-toolbar select[name="context"]', function(v) {
         const new_context = v.target.value;
         const dashboard = $('.webresources-dashboard');
         const current_view_mode = dashboard.data('view-mode');
         window.GlpiPluginWebResources.refreshDashboard(new_context, current_view_mode);
      });

      page.on('click', '.webresources-toolbar .webresources-view-mode', function() {
         const dashboard = $('.webresources-dashboard');
         const new_mode = dashboard.data('view-mode') === 'grid' ? 'list' : 'grid';
         const current_context = dashboard.data('context');
         window.GlpiPluginWebResources.refreshDashboard(current_context, new_mode);
      });

      page.on('keyup', '.webresources-toolbar input[name="search"]', function() {
         window.GlpiPluginWebResources.applySearchFilters(this);
      });
   },
   applySearchFilters: (search_el) => {
      const items = $('.webresources-item');
      const search_filter = search_el.value.toLowerCase();
      if (search_filter.length > 0) {
         items.each(function(i, v) {
            if (v.textContent.toLowerCase().includes(search_filter)) {
               $(v).show();
            } else {
               $(v).hide();
            }
         });
      } else {
         items.show();
      }
      const categories = $('.webresources-category');
      categories.each(function(i, v) {
         const cat = $(v);
         if (cat.find('.webresources-item').filter(function(i2, f) {
            return $(f).css('display') !== 'none';
         }).length === 0) {
            cat.hide();
         } else {
            cat.show();
         }
      });
   },
};

$(document).ready(function() {
   window.GlpiPluginWebResources.registerListeners();

   // If dashboard element exists, refresh it. Otherwise, wait for it to be created
   if ($('#webresources-content').length > 0) {
      const state = window.GlpiPluginWebResources.getDashboardState();
      window.GlpiPluginWebResources.refreshDashboard(state.context, state.view_mode);
   } else {
      // Use mutation observer to detect when dashboard element is created
      const mutationObserver = new MutationObserver(function(mutations) {
         mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
               // loop through added nodes
               for (let i = 0; i < mutation.addedNodes.length; i++) {
                  const node = mutation.addedNodes[i];
                  if (node.id === 'webresources-content') {
                     const state = window.GlpiPluginWebResources.getDashboardState();
                     window.GlpiPluginWebResources.refreshDashboard(state.context, state.view_mode);
                     mutationObserver.disconnect();
                  }
               }
            }
         });
      });
      mutationObserver.observe(document.body, {
         childList: true,
         subtree: true
      });
   }
});
