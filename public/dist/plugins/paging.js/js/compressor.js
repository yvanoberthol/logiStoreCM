(function(){
	var _fs = require('fs');
	var UglifyJS = require('uglify-js');


	var getUglifiedCode = function(code) {
		var ast, compressor;
		ast = UglifyJS.parse(code);
		ast.figure_out_scope();
		compressor = UglifyJS.Compressor({
			sequences     : true,  // join consecutive statemets with the “comma operator”
			properties    : true,  // optimize property access: a["foo"] → a.foo
			dead_code     : true,  // discard unreachable code
			drop_debugger : true,  // discard “debugger” statements
			unsafe        : false, // some unsafe optimizations
			conditionals  : true,  // optimize if-s and conditional expressions
			comparisons   : true,  // optimize comparisons
			evaluate      : true,  // evaluate constant expressions
			booleans      : true,  // optimize boolean expressions
			loops         : true,  // optimize loops
			unused        : true,  // drop unused variables/functions
			hoist_funs    : true,  // hoist function declarations
			hoist_vars    : false, // hoist variable declarations
			if_return     : true,  // optimize if-s followed by return/continue
			join_vars     : true,  // join var declarations
			cascade       : true,  // try to cascade `right` into `left` in sequences
			side_effects  : true,  // drop side-effect-free statements
			warnings      : true,  // warn about potentially dangerous optimizations/code
			global_defs   : {}     // global definitions
		});
		ast = ast.transform(compressor);
		return ast.print_to_string();
	};
	
	_fs.unlinkSync("js/paging.min.js");
	var code = getUglifiedCode(_fs.readFileSync("js/paging.js", 'utf8'));
	_fs.writeFileSync("js/paging.min.js", code, {encoding:'utf8'});

})();