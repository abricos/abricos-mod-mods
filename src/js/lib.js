var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'catalog', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isModerator: 45,
        isOperator: 40,
        isWrite: 30,
        isView: 10
    });

    NS.Application = {
        ATTRS: {},
        REQS: {},
        URLS: {
            ws: "#app={C#MODNAMEURI}/wspace/ws/",
            catalog: {
                manager: function(){
                    return this.getURL('ws') + 'catalog/CatalogManagerWidget/';
                },
                config: function(){
                    return this.getURL('ws') + 'catalogConfig/CatalogConfigWidget/';
                }
            }
        }
    };

    Y.mix(NS.Application, Brick.mod.catalog.Application, false, null, 0, true);

    SYS.Application.build(COMPONENT, {}, {
        initializer: function(){
            NS.roles.load(function(){
                this.initCallbackFire();
            }, this);
        }
    }, [], NS.Application);

    return; /////////////// TODO: old functions


    var Element = function(manager, d){
        Element.superclass.constructor.call(this, manager, d);
    };
    YAHOO.extend(Element, NSCat.Element, {
        update: function(d){
            Element.superclass.update.call(this, d);
        },
        url: function(){
            return '/mods/' + this.name + '/';
        }
    });
    NS.Element = Element;

    NS.initManager = function(callback){
        R.load(function(){
            NSCat.initManager('{C#MODNAME}', callback, {
                'roles': R,
                'ElementClass': NS.Element,
                'language': LNG,
                'elementNameChange': true,
                'elementNameUnique': true,
                'elementCreateBaseTypeDisable': true,
                'versionControl': true
            });
        });
    };

};