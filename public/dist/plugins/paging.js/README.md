paging.js
=========

paging.js is a simple, stylish and flexible pagination plugin for web developers!

![paginate](https://github.com/bvjebin/paging.js/raw/master/img/pagingjs.png)

----------------------------------------------------------------------

<br>
<h4>Installation:</h4>

Include CSS:

	<link rel="stylesheet" type="text/css" href="css/paging.css">

Include JS: 

jQuery must be included 

	<script type="text/javascript" src="js/jquery-1.10.2.js"></script> 

Include paging.js after jQuery

Development Version:(10Kb)

	<script type="text/javascript" src="js/paging.js"></script>

Compressed Version:(6.5Kb)

	<script type="text/javascript" src="js/paging.min.js"></script>

Call paging method on DOM element

	$("#id_selector, .class_selector").paging(); 


<br>
<h4>Expected HTML DOM structure:</h4>

<em>div structure</em>

	<div id="wrappper">
		<div>
			<div>Item1</div>
			<div>Item2</div>
		</div>
	</div>

<em>ul-li structure</em>

	<div id="wrappper">
		<ul>
			<li>Item1</li>
			<li>Item2</li>
		</ul>
	</div>

<br>
**Public APIs**

Get the pagination instance: 

	var pagingjsInstance = $("#selector").data("paging_js");

Get total number of pages:

	pagingjsInstance.getTotalPages();

Get current page number:

	pagingjsInstance.getCurrentPageNumber();

Draw page:

	pagingjsInstance.drawPage();

Go to Page: 

	pagingjsInstance.goToPage(page_number);

Go to next page:

	pagingjsInstance.goToNextPage();

Go to previous page:

	pagingjsInstance.goToPrevPage();

Check if current page is first page:

	pagingjsInstance.isFirstPage();

Check if current page is last page:

	pagingjsInstance.isLastPage();

Remove pagination and get the original DOM:

	pagingjsInstance.destroy();

<br>
**Available Options**

Number of items to show per page

	number_of_items: 4 //default: 5 | takes: non-zero numeral less than total limit

pagination type

	pagination_type: "full_numbers" // default full_numbers | takes: full_numbers | prev_next | first_prev_next_last

Number of buttons to show if in case of "full_numbers" pagination_type

	number_of_page_buttons: 3 //default 3 | takes: non-zero numeral less than total page size

Stealth Mode in case when no pagination should be shown but paginate using custom elements via pagination api

	stealth_mode: false //default false | takes: Boolean true | false

Color scheme

	theme: "light_connected" //default light_connected | takes: light_connected | light | blue | ""

Animate

	animate: true //default true | takes: true | false

Callbacks

	onBeforeInit: function(instance, $el) {}

	onAfterInit: function(instance, $el) {}

	onBeforeEveryDraw: function(instance, $pager) {}

	onAfterEveryDraw: function(instance, $pager) {}

	onFirstPage: function(instance, $pager) {}

	onLastPage: function(instance, $pager) {}

**Adding new theme:**

To add a new theme, add css styles to your stylesheet like below and specify the classname as value to <code>theme</code> option

	.pager.<your_classname> button {
		//Your styles here - normal state
	}
	.pager.<your_classname> button:hover {
		//Your styles here - hover state
	}
	.pager.<your_classname> button:active {
		//Your styles here - active state
	}
	.pager.<your_classname> button[disabled='disabled'] {
		//Your styles here - disabled state
	}

Upcoming features:
------------------
1) Various animation types

2) Slider type pagination style

3) Ajax handlings


Stay tuned! :)

