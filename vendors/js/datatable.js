(function(){
//Begin Closure

/**
* Jump To Widget for YUI Datable. To match the needs of W. 
* Use http://github.com/mattparker/YUI-paginator-ui-jump-to-page-drop-down/blob/master/lplt-paginator.js as example
* TK
*/ 


//End Closure
})();
(function() {
/**
* Turns a table into a JS table. 
* main todo/bugs list
*
* @todo low On initial load, initialPage isn't sticking visually. 
* 		Not critical, and it should be working, but not worth sinking any more time into at the moment. 
*/
var datatable = YAHOO.namespace('Plank.Table'),
	//Plank.bb is the global area to shove data that will be needed later. 
	bb = YAHOO.namespace('Plank.bb', 'Plank.bb.datatable');

var Dom = YAHOO.util.Dom,
	Lang = YAHOO.lang,
	Event = YAHOO.util.Event, 
	Selector = YAHOO.util.Selector;

var classes = {
	pgContainer: 'plank-pg-container' //Target class for the integrators
};

var KEY_SEP = '.'; //for later fixing.

/**
* Object which holds our templates for paginator. 
* This is something that will most likely change per project.
* For now, isolating it here. Maybe come up with something more clever later (ie: allow integr to build in HTML. and we just harvest that.)
*/
var pgTemplates = {
	template: '<div class="per_page">Show {RowsPerPageDropdown} Per Page</div>' 
				//Stub for Jump To Page
				+ '<div class="go_to_page"><form action="">'
				+ '<span>Jump to page: </span><input type="text" size="4" name=""><input id="submit" class="button" type="button" value="Go" name="sa">'
				+ '</form></div>'
				+ '<div class="paging">{FirstPageLink} {PreviousPageLink} {PageLinks} {NextPageLink} {LastPageLink}</div>',
	
	//And the individual elements
	previousPageLinkLabel: "< previous"
};

//Make them all pass through one custom formatter? there's a certain concistency we'll use (like published)
var myCustomFormatter = function(elCell, oRecord, oColumn, oData) {
 	elCell.innerHTML = oData;
};

//Ok, so this seems to work. If you want a custom formatter,can define like so. 
//mapping to label names for now, so generics like published can be dealt with. 
var formatter = {
	'published' : function(elCell, oRecord, oColumn, oData) {
		(1 == oData) ? Dom.addClass(elCell.parentNode, 'published') : Dom.addClass(elCell.parentNode, 'unpublished') ;
	},
	'actions' : function(elCell, oRecord, oColumn, oData) {
		var dataUrl = bb[this.getId()].dataUrl;
		var model = bb[this.getId()]['model'];

		var output = [];
		for (var i=0, j=oData.length; i < j; i++) {
			var url = '<a href="' 
			+ dataUrl.replace('index', oData[i]) 
			+ oRecord.getData()[model+KEY_SEP+'id']
			+ '">'
			+ oData[i]
			+ '</a>';
			output.push( url );
		}
		elCell.innerHTML = output.join(' ');
	}
};

var fixFormatter = function(el) {
	for(o in bb[el.id]['myColumnDefs'])	 {
//		bb[el.id]['myColumnDefs'][i].formatter = myCustomFormatter;
		bb[el.id]['myColumnDefs'][o].formatter = formatter[bb[el.id]['myColumnDefs'][o]['label']] || myCustomFormatter;

	}
};

/**
 * find or build the column definitions object for the table
 * checks the bb for the data first, returns it if it exists.
 * @param el Table we are building for
 * @return Obj column definitions required for datatable
 */
var getColDefs = function(el) {
	//Check the bulletin board for table data
	if(!Lang.isUndefined(bb[el.id]['myColumnDefs'])) {
		//fix format
		fixFormatter(el);
		return bb[el.id]['myColumnDefs'];
	 }

	var ths = Selector.query('thead>tr>th', el);

	var myColumnDefs = [];

	for (var i=0, j=ths.length; i < j; i++) {
		myColumnDefs.push({ key: ths[i].innerHTML, label: ths[i].innerHTML,  sortable:true});
	};

	//Cache it. duh!
	bb[el.id]['myColumnDefs'] = myColumnDefs;

	return myColumnDefs;	
};

//
/**
 * Calc if we have an initial sorted state to hook into.  
 *  sortedBy : {key:"id", dir:YAHOO.widget.DataTable.CLASS_ASC}
 * @param el Table element. 
 * @return object
 */

var getInitialSorted = function(el) {
	var ret = {};
	if(!Lang.isUndefined(bb[el.id])) {
		ret.key =  bb[el.id]['sort'] || null;
		ret.dir = bb[el.id]['dir'] || null;
	}
	return ret;
};

/**
 * Generated the paginator object.
 * @param el HTMLEl
 * @param datasource
 * @return YAHOO.widget.Paginator
 */
var getPaginator = function(el, ds) {

	//Force HTML table NOT to get overwritten. 
	//For when we spit out the table in one shot inline. ie: products index. 
	var rpp = 10;

	if(YAHOO.util.DataSource.TYPE_HTMLTABLE != ds.responseType && bb[el.id] && bb[el.id]['pageSize']) {
		rpp = bb[el.id]['pageSize'];
	}

	var lastDropDownOption = function(amount, totalRecords) {
		if (totalRecords < amount) return "All";
		return amount;
	}; 

	var paginator = new YAHOO.widget.Paginator({
			rowsPerPage: rpp,
			initialPage: bb[el.id].initialPage || 1,
			containerClass: classes.pgContainer,
			
			 //Hide if nothing to paginate!
			alwaysVisible: true,
			
			rowsPerPageOptions : [
				{ value: 10, text: '10' },
				{ value: 25, text: '25' },
				{ value: 50, text: '50' },
				{ value: 100, text: '100' },
				{ value: 200, text: lastDropDownOption(200, bb[el.id].totalRecords)}
			],
			//Will probably just switch to a object merge to merge in the pgTemplates obj
			template: pgTemplates.template,
			previousPageLinkLabel: pgTemplates.previousPageLinkLabel
		});

	return paginator;

};

/**
 * Figure out what sort of Datasource to use. 
 * ie: TYPE_HTMLTABLE, TYPE_JSON
 * @param el HTML Table
 * @return YAHOO.util.Datasource
 */
var getDataSource = function(el, columnDefs) {

	var ds, 
		source = bb[el.id]['dataUrl'] || el;

	ds = new YAHOO.util.DataSource(source);

	ds.responseType = (Lang.isUndefined(source.tagName)) ? YAHOO.util.DataSource.TYPE_JSON : YAHOO.util.DataSource.TYPE_HTMLTABLE;

	//uncouple schema for DT from DS schema. 
	//Do we have one generated on the backend?
	var responseSchemaColDefs;
	if(!Lang.isUndefined(bb[el.id]['myDataSourceFields'])) {
		responseSchemaColDefs = bb[el.id]['myDataSourceFields'];
	} else {
		responseSchemaColDefs = [];
		for(o in columnDefs){
			responseSchemaColDefs.push({"key": columnDefs[o].field});
		}		
	}
	
	ds.responseSchema = {
		fields: responseSchemaColDefs //don't bother setting parsers.so can just dipe myColumnDefs
	};	

	if(ds.responseType == YAHOO.util.DataSource.TYPE_JSON) {
		ds.responseSchema.resultsList = 'records';
		ds.responseSchema.metaFields = {totalRecords : "totalRecords"} ; // Access to value in the server response 

	}

	return ds;	
};

/**
* get the configs for creating the datatable
* @param el HTML El
* @param ds DataSource
*/
var	getConfig = function(el, ds) {

	var config = {
		paginator: getPaginator(el, ds),
	 	sortedBy : getInitialSorted(el)
	};

	if(ds.responseType == YAHOO.util.DataSource.TYPE_JSON) {
		config.dynamicData = true;
		config.initialPage = bb[el.id].initialPage || 1;
		config.initialRequest = cakeRequestBuilder(
			{
				pagination: { 
					page: config.initialPage
				},
				sortedBy: { 
					key: bb[el.id].sort || '',
					dir: 'yui-dt-' + (bb[el.id].dir || 'asc')
				}
			}
		); //base url...
		
		config.generateRequest = cakeRequestBuilder;
	}

	return config;
};

/**
* Set up the arrow listeners for left right table paging. 
* eventually up down will move through rows
*/
var keyListeners = function(paginator) {

	paginator.arrowPage = function(e, args) {
		var pageTo;
		if( 37 == args[0] ) { //left
			pageTo = this.getPreviousPage();
		} else if( 39 == args[0] ) { //right
			pageTo = this.getNextPage();
		}
		if(pageTo) { this.setPage(pageTo);}
	};

	var kl = new YAHOO.util.KeyListener(document, { keys:[37, 39] },
									   { fn:paginator.arrowPage, 
										 scope:paginator,
										 correctScope:true } );
	kl.enable();	
};

//@param req
//@param dt datatable. (optional)
var cakeRequestBuilder = function(req, dt) {

	var page = 'page:' + req.pagination.page;
	var sort = 'sort:' + req.sortedBy.key;	
	var dir = (req.sortedBy && req.sortedBy.dir === YAHOO.widget.DataTable.CLASS_DESC) ? "desc" : "asc"; 
	var direction = 'direction:' + dir;
	return [page,sort,direction].join('/');
};

var rowClickHandler = function() {
	var e = arguments[0]['event'], 
		target = arguments[0]['target'],
		id = this.getId();

	//Will need more cues, info stored on the datatable or in the dt bb.
	//ie: which data is the id, what is full path to view, etc...
	var rowData = this.getRecord(target).getData();

	var baseUrl = bb[id].dataUrl.replace('index', 'view')  
					+ rowData[bb[id]['model']+KEY_SEP+'id'];
	window.location.href = baseUrl;
};

var makeDatatable = function(table) {
	
	var myColumnDefs = getColDefs(table);
	var myDataSource = getDataSource(table, myColumnDefs);
	var myConfigs = getConfig(table, myDataSource);

	//Assumed format of div>table, so we use the parent of table as the container. 
	//YAHOO.widget.Logger.enableBrowserConsole();
	// YAHOO.widget.Logger.enableBrowserConsole();
	var myDataTable = new YAHOO.widget.DataTable(table.parentNode, myColumnDefs, myDataSource, myConfigs);
	
	//create a ref to this tables info based on its new id
	bb[myDataTable.getId()] = bb[table.id];
	
	myDataTable.handleDataReturnPayload = function(oRequest, oResponse, oPayload) {
        oPayload.totalRecords = oResponse.meta.totalRecords;
        return oPayload;
    };

	myDataTable.subscribe("rowMouseoverEvent", myDataTable.onEventHighlightRow); 
	myDataTable.subscribe("rowMouseoutEvent", myDataTable.onEventUnhighlightRow); 
	 //Subscribe to a click event to bind to 
	myDataTable.subscribe( 'rowClickEvent', rowClickHandler  );
 
	// YAHOO.widget.Logger.disableBrowserConsole();
	//YAHOO.widget.Logger.disableBrowserConsole();
	//Assume we want left/right paging controls
	keyListeners(myConfigs.paginator);
	
	
};

var init = function() {
	//Prog Enhancement
	var toTablize = Selector.query('div.plank-datatable > table');
	
	Dom.batch(toTablize, function(el) {
		//Using generateId to make sure el has an id. we dont have to check for it after that.
		Dom.generateId(el);
	 	makeDatatable(el);
	});	
};

Event.throwErrors = true; // @debug Remove this for production. 
Event.onDOMReady(init);

})();
