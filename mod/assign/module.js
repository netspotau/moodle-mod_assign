M.mod_assign = {};

M.mod_assign.init_tree = function(Y, expand_all, htmlid) {
    Y.use('yui2-treeview', function(Y) {
        var tree = new YAHOO.widget.TreeView(htmlid);

        tree.subscribe("clickEvent", function(node, event) {
            // we want normal clicking which redirects to url
            return false;
        });

        if (expand_all) {
            tree.expandAll();
        }
        tree.render();
    });
};

M.mod_assign.init_grading_table = function(Y, coursemoduleid) {
    Y.use('yui2-get', 'panel', 'dd-plugin', 'connection', 'dom', function (Y) {

        var gradingformpanel;

        var loadgradeformsuccess = function(o) {

            if (typeof(gradingformpanel) != "undefined") {
                gradingformpanel.hide();
                gradingformpanel.destroy();
            }
            var gradingformelement = Y.one('#gradingformajax');
            if (!gradingformelement) {
                Y.one('body').append('<div id="gradingformajax">&nbsp;</div>');
                gradingformelement = Y.one('#gradingformajax');
            }
            
            gradingformelement.setContent(o.responseText);



            var cancelbutton = Y.one('#id_cancelbutton');
            cancelbutton.on('click', function(e) {
                e.preventDefault();
                gradingformpanel.hide();
                gradingformpanel.destroy();
            });

            // hijack the submit

            var submitbutton = Y.one('#id_savegrade');
            submitbutton.on('click', function(e) {
                e.preventDefault();
    
                var savegradecallback = {
                    success : function(o) {
                        gradingformpanel.hide();
                        gradingformpanel.destroy();

                        if (o.responseText.length > 0) {
                            // validation error in the form
                            // reopen the panel
                            loadgradeformsuccess(o);
                        
                        } else {
                            // update the table row
                        }
                    },
                    failure : function(o) {
                        console.log(o);
                    }
                };

                var gradeform = Y.one('.gradeform').getDOMNode();
                var actionurl = gradeform.attributes['action'].value;
                YAHOO.util.Connect.setForm(gradeform);
                YAHOO.util.Connect.asyncRequest(gradeform.method, actionurl, savegradecallback);
                
            });

            
            // execute the javascript - in the correct order

            // force the javascript to execute
            var scriptnodes = Y.all('#gradingformajax script');

            function runNextScriptNode(nodelist) {
                var scriptnode = nodelist.shift();
                
                if (typeof(scriptnode) == "undefined") {
                    return;
                }
                scriptsrc = scriptnode.getAttribute('src');
                if (scriptsrc != "") {
                    // need to include this node and wait until it's loaded
                    YAHOO.util.Get.script(scriptsrc, {
                        onSuccess: function(o) {
                            runNextScriptNode(nodelist);
                        }
                    });
                        
                } else {
                    // normal script node - just run it
                    eval(scriptnode.getContent()); 
                    runNextScriptNode(nodelist);
                }

            }

            runNextScriptNode(scriptnodes);
            gradingformpanel = new Y.Panel({
                srcNode: gradingformelement,
                headerContent: M.str.moodle.grade,
                zIndex: 30,
                centered: true,
                visible: true,
                render: true,
                modal: true,
                iframe: true,
                constraintoviewport: false,
                plugins      : [Y.Plugin.Drag],
                buttons : []
            });



        };
        var loadgradeformfailure = function(o) {
        };

        var loadgradeformcallback = {
            success: loadgradeformsuccess,
            failure: loadgradeformfailure,
        };

        var ajaxlinks = Y.all('.ajaxgradelink');

        ajaxlinks.each(function(ajaxlink) {
            ajaxlink.on('click', function(e) {
                    e.preventDefault();
                    linkhref = ajaxlink.getAttribute('href');
                    linkhref += '&ajax=1';
                    
                    YAHOO.util.Connect.asyncRequest('GET', linkhref, loadgradeformcallback, null);
                }
            );
        });


    });
};

