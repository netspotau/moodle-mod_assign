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

        var loadgradeformsuccess = function(o) {
            var gradingformelement = Y.one('#gradingformajax');
            if (!gradingformelement) {
                Y.one('body').append('<div id="gradingformajax">&nbsp;</div>');
                gradingformelement = Y.one('#gradingformajax');
            }
            
            gradingformelement.setContent(o.responseText);

            var gradingformpanel = new Y.Panel({
                srcNode: gradingformelement,
                headerContent: M.str.moodle.grade,
                zIndex: 30,
                centered: true,
                visible: true,
                render: true,
                iframe: true,
                constraintoviewport: false,
                plugins      : [Y.Plugin.Drag],
                buttons: [],
                //buttons : [ { text:M.str.moodle.savechanges, handler:handleSubmit, isDefault:true }, 
                //            { text:M.str.moodle.cancel, handler:handleCancel } ] 
             
            });

            // execute the javascript - move them to the head of the page

            // force the javascript to execute
            var scriptnodes = Y.all('#gradingformajax script');

            var headnode = Y.one('head');
            var scriptsrcs = [];
            scriptnodes.each(function(node) {
                scriptsrc = node.getAttribute('src');
                if (scriptsrc != "") {
                    scriptsrcs[scriptsrcs.length] = scriptsrc;
                }
            });
            console.log(scriptsrcs);
            YAHOO.util.Get.script(scriptsrcs, {
                onSuccess: function(o) {
                    var scriptnodes = Y.all('#gradingformajax script');
                    scriptnodes.each(function(node) {
                        scriptsrc = node.getAttribute('src');
                        if (scriptsrc == "") {
                            eval(node.getContent());
                        }
                    });
                    
                }
            });


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

