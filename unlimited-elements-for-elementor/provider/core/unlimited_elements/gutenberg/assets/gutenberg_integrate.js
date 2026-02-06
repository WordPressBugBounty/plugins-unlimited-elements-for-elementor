var uelm_WidgetSettingsCache = [];
var uelm_WidgetSettingsCacheFlags = [];

(function (wp) {
    
	var g_debug = true;
    
    function trace(str){ console.log(str); }
    function debug(){ if(!g_debug) return; console.log.apply(console, arguments); }
    
    var wbe = wp.blockEditor;
    var wc  = wp.components;
    var wd  = wp.data;
    var we  = wp.element;
    var el  = we.createElement;

    jQuery(document).on("click", ".ue-gutenberg-widget-wrapper", function () {
        jQuery(this).closest("[tabindex]").focus();
    });
    // prevent navigation inside preview
    jQuery(document).on("click", ".ue-gutenberg-widget-wrapper a", function (event) {
        event.preventDefault();
    });

    jQuery(function(){ jQuery("body").append("<div id='div_debug' class='unite-div-debug'></div>"); });

    var edit = function (props) {

        var previewUrl = props.attributes._preview;
        if (previewUrl)
            return el("img", { src: previewUrl, style: { width: "100%", height: "auto" } });

        const blockProps = wbe.useBlockProps();

        // block options cache key
        const cacheKeyBase = props.clientId || props.attributes._id;

        // state
        const [widgetContent,     setWidgetContent]     = we.useState(null);
        const [settingsVisible,   setSettingsVisible]   = we.useState(false);
        const [settingsContent,   setSettingsContent]   = we.useState(null);
        const [isLoadingSettings, setIsLoadingSettings] = we.useState(false);

        // refs
        const widgetRef         = we.useRef(null);
        const widgetLoaderRef   = we.useRef(null);
        const widgetRequestRef  = we.useRef(null);

        const ucSettingsRef     = we.useRef(new UniteSettingsUC());
        const ucHelperRef       = we.useRef(new UniteCreatorHelper());

        const settingsInitedRef        = we.useRef(false);
        const initedSettingsElementRef = we.useRef(null);
        const lastSentDataRef          = we.useRef(null);
        const settingsObserverRef      = we.useRef(null);

        const ucSettings = ucSettingsRef.current;
        const ucHelper   = ucHelperRef.current;

        // orchestrator refs
        const didFirstPreviewRef    = we.useRef(false);
        const firstPreviewReadyRef  = we.useRef(false);
        const lastPreviewPayloadRef = we.useRef(null);
        const firstPreviewTimerRef  = we.useRef(null);

        function isSettingsReady() {
            return !!(settingsInitedRef.current &&
                      ucSettings &&
                      typeof ucSettings.isInited === 'function' &&
                      ucSettings.isInited());
        }

        function getWidgetRootEl(){
            const $root = jQuery(widgetRef.current);

            const $inner = $root.find('[data-uc-root]').first();
            return ($inner.length ? $inner : $root);
        }

        function applyStylePreviewOnce(maxTries = 30, delay = 50){

            if (!props.isSelected) {
                return;
            }

            let tries = 0;
            (function tick(){
                const $root = jQuery(widgetRef.current);
                const ready = isSettingsReady();

                if ($root.length && ready) {
                    updateSelectorsPreview();
                    return;
                }
                if (++tries < maxTries) setTimeout(tick, delay);
            })();
        }

        // --- delay for color/range/dimensions ---
        const saveDelayTimerRef = we.useRef(null);
        const lastChangeTypeRef    = we.useRef(null);
        const suppressNextReloadRef= we.useRef(false);

        const lastSelectorsCssRef  = we.useRef('');
        const lastIncludesHashRef  = we.useRef('');

        function applyLiveBoxModelInline(){
        	
            try{
                if (!isSettingsReady()) return;
                const vals = ucSettings.getSettingsValues ? ucSettings.getSettingsValues() : {};
                const pad  = vals?.advanced_padding || vals?.advanced_padding_row || vals?.padding || null;
                const mar  = vals?.advanced_margin  || vals?.advanced_margin_row  || vals?.margin  || null;

                const $root = getWidgetRootEl();
                if (!$root.length) return;

                const style = $root[0].style;

                function setBox(v, prefix){
                    if (!v || typeof v !== 'object') return;
                    const unit = v.unit || 'px';
                    const set = (prop, key) => {
                        if (v[key] === '' || typeof v[key] === 'undefined' || v[key] === null) {
                            style.removeProperty(prop);
                        } else {
                            style.setProperty(prop, String(v[key]) + unit);
                        }
                    };
                    set(prefix+'-top',    'top');
                    set(prefix+'-right',  'right');
                    set(prefix+'-bottom', 'bottom');
                    set(prefix+'-left',   'left');
                }

                setBox(pad, 'padding');
                setBox(mar, 'margin');
            }catch(e){}
        }

        // update oldCss with props from newCss
        function mergeCss(oldCss, newCss) {
            function createEmptyMap() {
                return {
                    scopesOrder: [],         
                    selectorsOrder: {},       
                    rules: {}               
                };
            }

            function ensureScope(map, scope) {
                if (!map.rules[scope]) {
                    map.rules[scope] = {};
                    map.selectorsOrder[scope] = [];
                    map.scopesOrder.push(scope);
                }
            }

            function addRuleToMap(map, scope, selector, body) {
                selector = selector && selector.trim();
                if (!selector) return;

                var decls = {};
                var parts = body.split(';');
                for (var i = 0; i < parts.length; i++) {
                    var part = parts[i].trim();
                    if (!part) continue;
                    var colonPos = part.indexOf(':');
                    if (colonPos === -1) continue;
                    var prop = part.slice(0, colonPos).trim();
                    var val  = part.slice(colonPos + 1).trim();
                    if (!prop || !val) continue;
                    decls[prop] = val;
                }

                if (!Object.keys(decls).length) return;

                ensureScope(map, scope);
                if (!map.rules[scope][selector]) {
                    map.rules[scope][selector] = {};
                    map.selectorsOrder[scope].push(selector);
                }

                var target = map.rules[scope][selector];
                for (var p in decls) {
                    target[p] = decls[p];
                }
            }

            function parseSimpleRulesInto(map, scope, css) {
                var len = css.length;
                var i = 0;
                while (i < len) {

                    while (i < len && /\s|;/.test(css[i])) i++;
                    if (i >= len) break;

                    if (css[i] === '}') {
                        i++;
                        continue;
                    }

                    var selStart = i;
                    while (i < len && css[i] !== '{' && css[i] !== '}') i++;
                    if (i >= len || css[i] === '}') {
                        i++;
                        continue;
                    }
                    var selector = css.slice(selStart, i).trim();
                    i++; 

                    var bodyStart = i;
                    var depth = 1;
                    while (i < len && depth > 0) {
                        if (css[i] === '{') depth++;
                        else if (css[i] === '}') depth--;
                        i++;
                    }
                    var body = css.slice(bodyStart, i - 1);

                    addRuleToMap(map, scope, selector, body);
                }
            }

            function parseCssToMap(css) {
                var map = createEmptyMap();
                if (!css) return map;

                var len = css.length;
                var i = 0;

                while (i < len) {

                    while (i < len && /\s|;/.test(css[i])) i++;
                    if (i >= len) break;

                    if (css[i] === '@') {
                        var atStart = i;
                        while (i < len && css[i] !== '{') i++;
                        if (i >= len) break;
                        var prelude = css.slice(atStart, i).trim(); 
                        i++; // '{'

                        var bodyStart = i;
                        var depth = 1;
                        while (i < len && depth > 0) {
                            if (css[i] === '{') depth++;
                            else if (css[i] === '}') depth--;
                            i++;
                        }
                        var body = css.slice(bodyStart, i - 1);

                        parseSimpleRulesInto(map, prelude, body);
                    } else if (css[i] === '}') {
                        i++;
                        continue;
                    } else {

                        var selStart = i;
                        while (i < len && css[i] !== '{' && css[i] !== '}') i++;
                        if (i >= len || css[i] === '}') {
                            i++;
                            continue;
                        }
                        var selector = css.slice(selStart, i).trim();
                        i++; 

                        var bodyStart2 = i;
                        var depth2 = 1;
                        while (i < len && depth2 > 0) {
                            if (css[i] === '{') depth2++;
                            else if (css[i] === '}') depth2--;
                            i++;
                        }
                        var body2 = css.slice(bodyStart2, i - 1);

                        addRuleToMap(map, '', selector, body2);
                    }
                }

                return map;
            }

            function mapToCss(map) {
                var out = '';

                for (var s = 0; s < map.scopesOrder.length; s++) {
                    var scope = map.scopesOrder[s];
                    var selectors = map.selectorsOrder[scope];
                    if (!selectors || !selectors.length) continue;

                    if (scope === '') {

                        for (var i = 0; i < selectors.length; i++) {
                            var sel = selectors[i];
                            var props = map.rules[scope][sel];
                            var keys = Object.keys(props);
                            if (!keys.length) continue;
                            out += sel + '{';
                            for (var k = 0; k < keys.length; k++) {
                                var prop = keys[k];
                                out += prop + ':' + props[prop] + ';';
                            }
                            out += '}';
                        }
                    } else {

                        var inner = '';
                        for (var j = 0; j < selectors.length; j++) {
                            var sel2 = selectors[j];
                            var props2 = map.rules[scope][sel2];
                            var keys2 = Object.keys(props2);
                            if (!keys2.length) continue;
                            inner += sel2 + '{';
                            for (var k2 = 0; k2 < keys2.length; k2++) {
                                var prop2 = keys2[k2];
                                inner += prop2 + ':' + props2[prop2] + ';';
                            }
                            inner += '}';
                        }
                        if (inner) {
                            out += scope + '{' + inner + '}';
                        }
                    }
                }

                return out;
            }

            var oldMap = parseCssToMap(oldCss || '');
            var newMap = parseCssToMap(newCss || '');

            for (var s = 0; s < newMap.scopesOrder.length; s++) {
                var scope = newMap.scopesOrder[s];
                var newSelectors = newMap.selectorsOrder[scope];
                if (!newSelectors || !newSelectors.length) continue;

                ensureScope(oldMap, scope);

                for (var i = 0; i < newSelectors.length; i++) {
                    var sel = newSelectors[i];
                    var newProps = newMap.rules[scope][sel];

                    if (!oldMap.rules[scope][sel]) {
                        oldMap.rules[scope][sel] = {};
                        oldMap.selectorsOrder[scope].push(sel);
                    }

                    var target = oldMap.rules[scope][sel];
                    for (var p in newProps) {
                        target[p] = newProps[p];
                    }
                }
            }

            let ret = mapToCss(oldMap);

            return ret;
        }

       /**
        * update selectors preview
        */
        function updateSelectorsPreview() {
        	
        	var isDetailedDebug = false;
        	
        	debug("run func: updateSelectorsPreview");
        	                    	
            if (!props.isSelected) {
            	
            	if(isDetailedDebug == true)
            		trace("not selected, exit");
            	
                return;
            }

            if (!isSettingsReady()) {
            	
            	if(isDetailedDebug == true)
            		trace("not ready, exit");
            	
                return;
            }
            const $root = jQuery(widgetRef.current);
            if ($root.length === 0) {
            	
            	if(isDetailedDebug == true)
            		trace("not root, exit");
            	
                return;
            }
                        
            const css = (ucSettings.getSelectorsCss && ucSettings.getSelectorsCss()) || '';
            
            if (css && css !== lastSelectorsCssRef.current) {

                if ($root.find("[name=uc_selectors_css]").length === 0) {
                    $root.prepend('<style name="uc_selectors_css"></style>');
                }
                const $style = $root.find('[name=uc_selectors_css]');
                const prevCss = $style.text() || '';

                const mergedCss = mergeCss(prevCss, css);

                $style.text(mergedCss);
                lastSelectorsCssRef.current = mergedCss;
                
            	if(isDetailedDebug == true)
            		trace("merge css!");
                
            }else{
            	
            	if(isDetailedDebug == true)
            		trace("not root, exit");
            	
            }

            const includes = ucSettings.getSelectorsIncludes && ucSettings.getSelectorsIncludes();
            const includesHash = stableStringify(includes || {});
            if (includes && includesHash !== lastIncludesHashRef.current) {
                ucHelper.putIncludes(getPreviewWindowElement(), includes);
                lastIncludesHashRef.current = includesHash;
            }

            applyLiveBoxModelInline();

        }
        
        /**
         * flush save new
         * @param typeOverride
         */
        function flushSaveNow(typeOverride) {
            if (!isSettingsReady()) return;
            if (saveDelayTimerRef.current) {
                clearTimeout(saveDelayTimerRef.current);
                saveDelayTimerRef.current = null;
            }
            try {
                const currentObj = (function(){
                    try { return props.attributes.data ? JSON.parse(props.attributes.data) : {}; }
                    catch(e){ return {}; }
                })();
                const snapshot  = ucSettings.getSettingsValues() || {};
                const mergedObj = { ...currentObj, ...snapshot };
                const mergedStr = JSON.stringify(mergedObj);

                if (lastSentDataRef.current === mergedStr) return;

                try {
                    delete uelm_WidgetSettingsCache[cacheKeyBase];
                    delete uelm_WidgetSettingsCache[cacheKeyBase + '_settings'];

                    uelm_WidgetSettingsCacheFlags[cacheKeyBase] = false;
                    uelm_WidgetSettingsCacheFlags[cacheKeyBase + '_settings'] = false;
                } catch(e){}

                lastSentDataRef.current = mergedStr;

                const tRaw = (typeOverride ?? lastChangeTypeRef.current ?? '').toString().toLowerCase().trim();

                suppressNextReloadRef.current = (tRaw == 'styles'); // suppress block reloading

                props.setAttributes({ data: mergedStr });

            } finally { }
        }

        var isEditorSidebarOpened = wd.useSelect(function (select) {
            return select("core/edit-post").isEditorSidebarOpened();
        });
        var activeGeneralSidebarName = wd.useSelect(function (select) {
            return select("core/edit-post").getActiveGeneralSidebarName();
        });
        var previewDeviceType = wd.useSelect((select) => {
            const editor = select(wp.editPost?.store || "core/edit-post");
            return editor.getDeviceType?.() || editor.__experimentalGetPreviewDeviceType?.() || "Desktop";
        }, []);

        var widgetId      = "ue-gutenberg-widget-"   + props.clientId;
        var settingsId    = "ue-gutenberg-settings-" + props.clientId;
        var settingsErrorId = settingsId + "-error";

        function stableStringify(obj){
            try { return JSON.stringify(obj, Object.keys(obj).sort()); }
            catch(e){ return ""; }
        }

        var getSettings = function () {
            try { return props.attributes.data ? JSON.parse(props.attributes.data) : null; }
            catch (e) { return null; }
        };
        var getSettingsElement = function () {
            return jQuery("#" + settingsId);
        };
        var getPreviewWindowElement = function () {
            return window.frames["editor-canvas"] || window;
        };

        var initSettings = function () {
        	
        	debug("init settings!");
        	
            var $settingsElement = getSettingsElement();
            if (!$settingsElement || $settingsElement.length === 0) return;

            var elem = $settingsElement[0];

            if (ucSettings.isInited() && initedSettingsElementRef.current === elem) {
                return;
            }

            if (ucSettings.isInited() && initedSettingsElementRef.current !== elem) {
                ucSettings.destroy();
            }

            // get values before init
            var values = getSettings();

            if (values === null) {
                try {
                    // get defaults from data-itemvalues
                    var defaultValues = {};
                    
                    $settingsElement.find('.unite-setting-repeater').each(function() {
                        var $repeater = jQuery(this);
                        var repeaterName = $repeater.attr('name') || $repeater.data('name');
                        var defaultItems = $repeater.data('itemvalues');
                        
                        if (repeaterName && defaultItems && Array.isArray(defaultItems) && defaultItems.length > 0) {
                            defaultValues[repeaterName] = defaultItems;
                            debug('from integrate: loaded default items for', repeaterName);
                            debug(defaultItems);
                        }
                    });
                    
                    // if defaults exists then use it
                    if (Object.keys(defaultValues).length > 0) {
                        values = defaultValues;
                    }
                    
                } catch (e) {
                    console.warn('Failed to get default settings before init', e);
                    values = null;
                }
            }

            ucSettings.init($settingsElement, { cacheValues: values });
            initedSettingsElementRef.current = elem;

            ucSettings.setSelectorWrapperID(widgetId);
            ucSettings.setResponsiveType(previewDeviceType.toLowerCase());

            function handleSettingsEvent(evt, payload) {

                const name = (payload?.name ?? '').toString();

                const type = (payload?.type ?? '').toString().toLowerCase().trim();
                const STYLE_ONLY_TYPES = ['color','range','dimensions'];
                const $c = jQuery('#' + settingsId +' [data-name="' + payload.name + '"] [data-selectors]');

                if (STYLE_ONLY_TYPES.includes(type) && $c.length > 0) {
                    // style only fields
                    lastChangeTypeRef.current = 'styles';

                    if (saveDelayTimerRef.current) {
                        clearTimeout(saveDelayTimerRef.current);
                        saveDelayTimerRef.current = null;
                    }
                    saveDelayTimerRef.current = setTimeout(() => {
                        flushSaveNow('styles'); 
                    }, 180);
                } else {
                    // content fields (reload block preview)
                    lastChangeTypeRef.current = 'content';

                    if (saveDelayTimerRef.current) {
                        clearTimeout(saveDelayTimerRef.current);
                        saveDelayTimerRef.current = null;
                    }
                    flushSaveNow(type); 
                }
            }
            
            ucSettings.setEventOnChange(handleSettingsEvent);
            if (typeof ucSettings.onEvent === 'function') {
                ucSettings.onEvent('settings_instant_change', handleSettingsEvent);
            }

            ucSettings.setEventOnSelectorsChange(function () {
            	
                if (!isSettingsReady()) 
                	return;

                updateSelectorsPreview();

                lastChangeTypeRef.current = 'styles';

                flushSaveNow('styles');
            });

            ucSettings.setEventOnResponsiveTypeChange(function (event, type) {
                uelm_WidgetSettingsCacheFlags[cacheKeyBase] = true;
                uelm_WidgetSettingsCacheFlags[cacheKeyBase + '_settings'] = true;

                var deviceType = type.charAt(0).toUpperCase() + type.substring(1);
                const editorStore = wp.editPost?.store || "core/edit-post";
                const dispatcher = wp.data.dispatch(editorStore);

                if (typeof dispatcher.setDeviceType === "function") {
                    dispatcher.setDeviceType(deviceType);
                } else if (typeof wp.data.dispatch("core/edit-post").__experimentalSetPreviewDeviceType === "function") {
                    wp.data.dispatch("core/edit-post").__experimentalSetPreviewDeviceType(deviceType);
                }
            });

            if (values !== null) {
                debug('from integrate: setting cache after init');
                debug(values);
                ucSettings.setCacheValues(values);
                
                // save attrs for the new block
                if (!props.attributes.data) {
                    props.setAttributes({ data: JSON.stringify(values) });
                }
            }

            settingsInitedRef.current = true;
        };

        // init settings when panel visible + settings HTML ready + real DOM exists
        function maybeInitSettings(){
        	
            if (!settingsVisible) 
            	return;
            
            if (!settingsContent) 
            	return;
            
            var $settingsElement = getSettingsElement();
            if (!$settingsElement || $settingsElement.length === 0) return;

            var elem = $settingsElement[0];
            if (!ucSettings.isInited() || initedSettingsElementRef.current !== elem) {
                initSettings();
            }
        }

        function attachSettingsObserver(){
            detachSettingsObserver();
            var observer = new MutationObserver(function(){
                maybeInitSettings();
            });
            observer.observe(document.body, { childList: true, subtree: true });
            settingsObserverRef.current = observer;
        }

        function detachSettingsObserver(){
            if (settingsObserverRef.current) {
                settingsObserverRef.current.disconnect();
                settingsObserverRef.current = null;
            }
        }

        // AJAX: load settings HTML
        var loadSettingsContent = function () {
            var widgetCacheKey = cacheKeyBase + '_settings';

            setIsLoadingSettings(true);

            if ( uelm_WidgetSettingsCache[widgetCacheKey] && uelm_WidgetSettingsCacheFlags[widgetCacheKey] ) {
                uelm_WidgetSettingsCacheFlags[widgetCacheKey] = false;
                setSettingsContent( uelm_WidgetSettingsCache[widgetCacheKey] );
                setIsLoadingSettings(false);
                return;
            }

            g_ucAdmin.setErrorMessageID(settingsErrorId);

            const urlParams = new URLSearchParams(window.location.search);
            const isTestFreeVersion = urlParams.get("testfreeversion") === "true";

            var requestData = {
                id: props.attributes._id,
                config: getSettings(),
                platform: "gutenberg",
                source: "editor"
            };
            if (isTestFreeVersion) requestData.testfreeversion = true;

            g_ucAdmin.ajaxRequest("get_addon_settings_html", requestData, function (response) {

                var html = g_ucAdmin.getVal(response, "html");

                uelm_WidgetSettingsCache[widgetCacheKey] = html;
                uelm_WidgetSettingsCacheFlags[widgetCacheKey] = true;

                setSettingsContent(html);
                setIsLoadingSettings(false);
            }).fail(function() {
                 setIsLoadingSettings(false);
            });
        };

        // AJAX: load widget HTML
        var loadWidgetContent = function (overrideSettings) {
            var widgetCacheKey = cacheKeyBase;

            if ( uelm_WidgetSettingsCache[widgetCacheKey] && uelm_WidgetSettingsCacheFlags[widgetCacheKey] ) {
                uelm_WidgetSettingsCacheFlags[widgetCacheKey] = false;
                initWidget( uelm_WidgetSettingsCache[widgetCacheKey] );
                debug('loadWidgetContent loaded from cache');
                return;
            }

            if (!widgetContent) {

                if (typeof window.uelm_setBlocks === 'undefined') {
                    window.uelm_setBlocks = new Set();
                }
                
                var blockKey = props.name + '_' + props.clientId;

                if (window.uelm_setBlocks.has(blockKey)) {
                    // Block already processed, skipping
                    return;
                }
                
                for (var i = 0; i < g_gutenbergParsedBlocks.length; i++) {
                    var block = g_gutenbergParsedBlocks[i];

                    if (block && block.name === props.name && block.html) {
                        
                        setWidgetContent(block.html);

                        try {
                            if (isSettingsReady()) {
                                var values = getSettings(); 
                                if (values !== null) {
                                    ucSettings.setCacheValues(values);
                                }
                            }
                        } catch (e) {
                            console.warn('Failed to sync ucSettings from data after load-from-page', e);
                        }

                        uelm_WidgetSettingsCache[widgetCacheKey] = {
                            html: block.html,
                            includes: {}   
                        };
                        uelm_WidgetSettingsCacheFlags[widgetCacheKey] = true;
                        
                        delete g_gutenbergParsedBlocks[i];
                        
                        window.uelm_setBlocks.add(blockKey);
                        
                        debug('loadWidgetContent loaded from page');
                        return;
                    }
                }
                
                console.warn('No content found for block:', blockKey);
            }

            var settings = overrideSettings ?? getSettings();

            if (widgetRequestRef.current !== null)
                widgetRequestRef.current.abort();

            var loaderElement = jQuery(widgetLoaderRef.current);
            loaderElement.show();

            debug('loadWidgetContent load from server');

            widgetRequestRef.current = g_ucAdmin.ajaxRequest("get_addon_output_data", {
                id: props.attributes._id,
                root_id: props.attributes._rootId,
                platform: "gutenberg",
                source: "editor",
                settings: settings || null,
                selectors: true,
            }, function (response) {
                uelm_WidgetSettingsCache[widgetCacheKey] = response;
                uelm_WidgetSettingsCacheFlags[widgetCacheKey] = true;
                initWidget(response);
            }).always(function () {
                loaderElement.hide();
            });

        };

        var initWidget = function (response) {
            var html = g_ucAdmin.getVal(response, "html");
            var includes = g_ucAdmin.getVal(response, "includes");
            var win = getPreviewWindowElement();

            if (win.jQuery && Array.isArray(includes?.scripts)) {
                includes.scripts = includes.scripts.filter(function (src) {
                    return !/jquery(\.min)?\.js/i.test(src);
                });
            }

            ucHelper.putIncludes(win, includes, function () {
                setWidgetContent(html);
            });

            applyStylePreviewOnce();
        };

        we.useEffect(function () {
            debug('[effect 1]');

            jQuery("#unlimited-elements-styles").remove();

            attachSettingsObserver();
            loadWidgetContent();

            return function () {
                // cleanup on unmount
                if (firstPreviewTimerRef.current) {
                    clearTimeout(firstPreviewTimerRef.current);
                    firstPreviewTimerRef.current = null;
                }

                if (isSettingsReady()) flushSaveNow();

                ucSettings.destroy();
                initedSettingsElementRef.current = null;
                settingsInitedRef.current = false;
                detachSettingsObserver();
            };
        }, []);

        we.useEffect(function () {
            debug('[effect 2]');
            if (didFirstPreviewRef.current) return;

            const attr = (() => {
                try { return props.attributes.data ? JSON.parse(props.attributes.data) : {}; }
                catch(e){ return {}; }
            })();

            if (attr && Object.keys(attr).length > 0) {
                const payloadStr = JSON.stringify(attr);
                lastPreviewPayloadRef.current = payloadStr;

                loadWidgetContent(attr);               
                didFirstPreviewRef.current    = true; 
                firstPreviewReadyRef.current  = true;  
            }
        }, []);

        // mark color/range/dimensions to suppress reload
        we.useEffect(function () {

            debug('[effect 3]');

            function checkEventType(e){

                const t = e.target;
                if (!t) return;

                const settingsRoot = document.getElementById(settingsId);
                if (!settingsRoot || !settingsRoot.contains(t)) return;

                const type         = (t.type || '').toLowerCase();
                const hasColor     = t.classList?.contains('unite-color-picker');
                const isRange      = type === 'range' || t.classList?.contains('unite-range-slider');
                const isDimensions = !!t.closest?.('.unite-dimensions');

                if (hasColor) {
                    lastChangeTypeRef.current = 'color';
                } else if (isRange || isDimensions) {
                    lastChangeTypeRef.current = isDimensions ? 'dimensions' : 'range';
                }
            }

            document.addEventListener('input',  checkEventType, true);
            document.addEventListener('change', checkEventType, true);

            return () => {
                document.removeEventListener('input',  checkEventType, true);
                document.removeEventListener('change', checkEventType, true);
            };
        }, [settingsId]);

        // insert widget HTML into DOM
        we.useEffect(function () {
            debug('[effect 4]');

            if (!widgetContent) return;
            jQuery(widgetRef.current).html(widgetContent);

        }, [widgetContent]);

        // sidebar visibility logic
        we.useEffect(function () {
            debug('[effect 5]');
            const isVisible = props.isSelected
                && isEditorSidebarOpened
                && activeGeneralSidebarName === "edit-post/block";

            setSettingsVisible(isVisible);

            if (isVisible && !settingsContent && !isLoadingSettings) {
                loadSettingsContent();
            } else if (!isVisible) {
                if (isSettingsReady()) flushSaveNow();
            }
        }, [props.isSelected, isEditorSidebarOpened, activeGeneralSidebarName]);

        we.useEffect(function () {
            debug('[effect 6]');
            if (ucSettings.isInited())
                ucSettings.setResponsiveType(previewDeviceType.toLowerCase());
        }, [previewDeviceType]);

        we.useEffect(function () {
            
        	debug('[effect 7]');
            
            maybeInitSettings();
                        
        }, [settingsVisible, settingsContent, previewDeviceType, props.attributes.data]);

        we.useEffect(function () {
        	
            debug('[effect 8]');
            
            if (!settingsContent) return;
            	runFirstPreviewOnce();
            
        }, [settingsContent]);

        we.useEffect(function () {
            
        	debug('[effect 9]');
        	
            if (!firstPreviewReadyRef.current) {
                if (widgetContent) {
                    firstPreviewReadyRef.current = true;
                } else {
                    return;
                }
            }
            
            debug("check the settings");
            
            if (suppressNextReloadRef.current) {

                suppressNextReloadRef.current = false;

                debug("effect 9 - supress next reload");
                
                updateSelectorsPreview();
                                
                return;
            }

            const settings = (() => {
                try { 
                    return props.attributes.data ? JSON.parse(props.attributes.data) : {}; 
                } catch(e){ 
                    return {}; 
                }
            })();
            
            trace("the settings");
            trace(settings);
            
            const payloadStr = JSON.stringify(settings || {});
            if (payloadStr === lastPreviewPayloadRef.current) {
            	
            	debug("Update Selectors Preview");
            	
                updateSelectorsPreview();
                return;
            }
            lastPreviewPayloadRef.current = payloadStr;

            loadWidgetContent(settings);

        }, [props.attributes.data]);

        // set focus and mouse events
        we.useEffect(function () {
        	
            debug('[effect 10]');

            function onBlur(e){
                const t = e.target;
                if (!t) return;
                const tag = (t.tagName || '').toLowerCase();
                if (tag === 'input' || tag === 'textarea' || tag === 'select') {
                    if (isSettingsReady()) flushSaveNow();
                }
            }

            function onBeforeUnload() {
                if (isSettingsReady()) flushSaveNow();
            }

            function onPointerUpOrTouchEnd() {
                const t = (lastChangeTypeRef.current || '').toLowerCase();
                if (t === 'styles') {
                    flushSaveNow(t); 
                }
            }

            document.addEventListener('blur', onBlur, true);
            window.addEventListener('beforeunload', onBeforeUnload);
            window.addEventListener('pointerup', onPointerUpOrTouchEnd, { passive: true });
            window.addEventListener('touchend',  onPointerUpOrTouchEnd, { passive: true });

            return () => {
                window.removeEventListener('pointerup', onPointerUpOrTouchEnd);
                window.removeEventListener('touchend',  onPointerUpOrTouchEnd);
                window.removeEventListener('beforeunload', onBeforeUnload);
                document.removeEventListener('blur', onBlur, true);
            };
        }, []);

        function runFirstPreviewOnce(maxTries = 30, delay = 50) {
            if (didFirstPreviewRef.current) return;
            if (firstPreviewTimerRef.current) return;

            let tries = 0;

            const tick = () => {
                if (didFirstPreviewRef.current) { firstPreviewTimerRef.current = null; return; }

                if (!isSettingsReady()) {
                    if (++tries < maxTries) firstPreviewTimerRef.current = setTimeout(tick, delay);
                    else firstPreviewTimerRef.current = null;
                    return;
                }

                const attr = (() => { try { return props.attributes.data ? JSON.parse(props.attributes.data) : {}; } catch(e){ return {}; } })();

                if (attr?.uc_items?.length) {
                    const payloadStr = JSON.stringify(attr);
                    lastPreviewPayloadRef.current = payloadStr;
                    loadWidgetContent(attr);
                    didFirstPreviewRef.current    = true;
                    firstPreviewReadyRef.current  = true;
                    firstPreviewTimerRef.current  = null;
                    return;
                }

                const def = ucSettings.getSettingsValues?.() || {};
                if (!def?.uc_items?.length) {
                    if (++tries < maxTries) firstPreviewTimerRef.current = setTimeout(tick, delay);
                    else firstPreviewTimerRef.current = null;
                    return;
                }

                const merged = { ...def, ...attr };
                const mergedStr = JSON.stringify(merged);

                if (lastSentDataRef.current !== mergedStr) {
                    suppressNextReloadRef.current = true;
                    lastSentDataRef.current = mergedStr;
                    props.setAttributes({ data: mergedStr });
                }

                lastPreviewPayloadRef.current = mergedStr;
                
                loadWidgetContent(merged); 

                didFirstPreviewRef.current    = true;
                firstPreviewReadyRef.current  = true;
                firstPreviewTimerRef.current  = null;
            };

            tick();
        }

        var settings = el(
            wbe.InspectorControls, {},
            el("div", { className: "ue-gutenberg-settings-error", id: settingsErrorId }),
            settingsContent && el("div", { id: settingsId, dangerouslySetInnerHTML: { __html: settingsContent } }),
            !settingsContent && isLoadingSettings && el("div", { className: "ue-gutenberg-settings-spinner" }, el(wc.Spinner)),
            !settingsContent && !isLoadingSettings && el("div", null, "No settings found or error occured."),
        );

        var widget = el(
            "div", { className: "ue-gutenberg-widget-wrapper" },
            widgetContent && el("div", { className: "ue-gutenberg-widget-content", id: widgetId, ref: widgetRef }),
            widgetContent && el("div", { className: "ue-gutenberg-widget-loader", ref: widgetLoaderRef }, el(wc.Spinner)),
            !widgetContent && el("div", { className: "ue-gutenberg-widget-placeholder" }, el(wc.Spinner)),
        );

        return el("div", blockProps, settings, widget);
    };

    for (var name in g_gutenbergBlocks) {
        var block = g_gutenbergBlocks[name];
        var args  = jQuery.extend(block, { edit: edit });

        // convert inline SVG icon string to element
        if (typeof args.icon === 'string' && args.icon.trim().startsWith('<svg')) {
            try {
                const sanitized = args.icon.trim();
                args.icon = el('span', { dangerouslySetInnerHTML: { __html: sanitized } });
            } catch (e) {
                args.icon = '';
            }
        }
        wp.blocks.registerBlockType(name, args);
    }
})(wp);
