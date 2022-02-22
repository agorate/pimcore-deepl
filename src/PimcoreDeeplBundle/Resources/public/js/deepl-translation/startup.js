pimcore.registerNS("pimcore.plugin.deeplTranslate");

pimcore.plugin.deeplTranslate = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return "pimcore.plugin.deeplTranslate";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },
    createTranslation: function (document) {
        var filteredWebsiteLanguages = pimcore.settings.websiteLanguages.filter(function(value, index, arr) {
            return value !== document.data.properties.language.data;
        }); // Do not display the language of the current document

        var pageForm = new Ext.form.FormPanel({
            border: false,
            defaults: {
                labelWidth: 170
            },
            items: [{
                xtype: "combo",
                name: "language",
                store: filteredWebsiteLanguages,
                editable: false,
                triggerAction: 'all',
                mode: "local",
                fieldLabel: t('language'),
                listeners: {
                    select: function (el) {
                        if (el.getValue() === 'de_DE') {
                            pageForm.getComponent("parent").setValue('/de/articles');
                        } else {
                            pageForm.getComponent("parent").setValue('/'+el.getValue()+'/articles');
                        }
                    }.bind(this)
                }
            }, {
                xtype: "textfield",
                name: "parent",
                itemId: "parent",
                width: "100%",
                fieldCls: "input_drop_target",
                fieldLabel: t("parent"),
                listeners: {
                    "render": function (el) {
                        new Ext.dd.DropZone(el.getEl(), {
                            reference: this,
                            ddGroup: "element",
                            getTargetFromEvent: function (e) {
                                return this.getEl();
                            }.bind(el),

                            onNodeOver: function (target, dd, e, data) {
                                // Allow only documents to be set as parent
                                if (data.records.length === 1 && data.records[0].data.elementType === "document") {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                }
                            },

                            onNodeDrop: function (target, dd, e, data) {
                                if (!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                                    return false;
                                }

                                data = data.records[0].data;
                                if (data.elementType === "document") {
                                    this.setValue(data.path);
                                    return true;
                                }
                                return false;
                            }.bind(el)
                        });
                    }
                }
            }]
        });

        var win = new Ext.Window({
            width: 600,
            bodyStyle: "padding:10px",
            items: [pageForm],
            buttons: [{
                text: t("cancel"),
                iconCls: "pimcore_icon_cancel",
                handler: function () {
                    win.close();
                }
            }, {
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    var params = pageForm.getForm().getFieldValues();
                    var id = document.data.id;
                    win.disable();
                    Ext.Ajax.request({
                        url: '/admin/deeplTranslateDocument',
                        method: "post",
                        params: {
                            language: params.language,
                            id: id,
                            parent: params.parent
                        },
                        success: function (response) {
                            var res = Ext.decode(response.responseText);
                            if (res.success) {
                                Ext.MessageBox.alert(t('Success'), 'Successfully translated document to "' + params.parent + '" named "' + res.key + '"');
                                pimcore.helpers.openDocument(res.id, "page");
                            } else {
                                Ext.MessageBox.alert(t('Error'), t(res.message));
                                win.enable();
                            }
                        }.bind(this)
                    });
                    win.close();
                }.bind(this)
            }]
        });

        win.show();
    },

    postOpenDocument: function (document, type) {
        if (type !== 'page') {
            return; // Do not show if selected element is not a page
        }
        if (document.data.path === '/') {
            return; // Do not show translation in root path. Should not be translatable
        }

        let menuParent = document.toolbar.items.items[9].btnInnerEl.component.menu.items.items[0].menu;

        menuParent.add({
            text: t('Deepl Translation'),
            iconCls: 'pimcore_material_icon_translation',
            scale: 'small',
            handler: this.createTranslation.bind(this, document),
        });
    }
});

var deeplTranslatePlugin = new pimcore.plugin.deeplTranslate();