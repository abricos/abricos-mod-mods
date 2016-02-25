var Component = new Brick.Component();
Component.requires = {
    yui: ['base'],
    mod: [
        {name: 'catalog', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var R = NS.roles,
        LNG = this.language,
        NSCat = Brick.mod.catalog;

    this.buildTemplate({}, '');

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