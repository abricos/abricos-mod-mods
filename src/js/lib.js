var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'catalog', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        L = Y.Lang,
        COMPONENT = this,
        SYS = Brick.mod.sys,
        NSCat = Brick.mod.catalog;

    SYS.Application.build(COMPONENT,
        NSCat.Application.ajaxes,
        NSCat.Application.px,
        [],
        NSCat.Application.sx
    );

    /*
    NS.App.ATTRS = Y.merge(NS.App.ATTRS, {
        ElementTypeClass: {
            value: null
        }

    });
    /**/

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isModerator: 45,
        isOperator: 40,
        isWrite: 30,
        isView: 10
    });

    var L = YAHOO.lang,
        R = NS.roles;

    var SysNS = Brick.mod.sys;
    var LNG = this.language;

    var buildTemplate = this.buildTemplate;
    buildTemplate({}, '');

    NS.lif = function(f){
        return L.isFunction(f) ? f : function(){
        };
    };
    NS.life = function(f, p1, p2, p3, p4, p5, p6, p7){
        f = NS.lif(f);
        f(p1, p2, p3, p4, p5, p6, p7);
    };
    NS.Item = SysNS.Item;
    NS.ItemList = SysNS.ItemList;

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


    var WS = "#app={C#MODNAMEURI}/wspace/ws/";
    NS.navigator = {
        'home': function(){
            return WS;
        },
        'catalogman': function(catid){
            var link = WS + 'catalog/CatalogManagerWidget/';
            if (catid && catid * 1 > 0){
                link += catid + '/';
            }
            return link;
        },
        'catalogconfig': function(){
            return WS + 'catalogconfig/CatalogConfigWidget/';
        },
        'go': function(url){
            Brick.Page.reload(url);
        }
    };

    NS.manager = null;

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