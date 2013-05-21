/*
 * Form Collection
 */
(function($, Backbone, _){

    var ExposeTranslation = window.ExposeTranslation || {'get' : function(key){ return key; }}

    var MasterView = Backbone.View.extend({
        "tagName"    : "div",
        "className"    : "content",
        "icon" : '<i class="icon-plus-sign"></i>',
        "addlink" : _.template('<a href="#" class="add_form_collection_link"><%= icon %> <%= label %></a>'),
        "legend" : _.template('<h<%= heading %> class="collection-heading"><%= label %> <%= counter %></h<%= heading %>>'),
        "counter" : 0,
        "heading" : 2,

        "initialize": function () {
            this.render();
        },
        "render" : function() {

            var $this = this,
                add_link = $(this.createAddLink()).on("click", function(e) { e.preventDefault(); $this.addForm($this, this); });

            this.$el.before(add_link);

            if(this.$el.find('fieldset:first').children().length > 0) {
                var options = {"heading" : 3, "label" : $this.options.label, "counter" : ++$this.counter};
                this.$el.find('fieldset:first').prepend($this.legend(options));
            }

            return this;
        },
        "createAddLink" : function(){
            return this.addlink({"icon" : this.icon, "label" : ExposeTranslation.get('Add') + " " + this.options.label});
        },
        "addForm" : function($this, l) {

            var total = this.$el.hasClass('child') ? $('.content', this.$el).length : 0;

            total += this.$el.siblings('.child').length;

            var prototype  = this.$el.attr('data-prototype'),
                form = $(prototype.replace(/__name__/g, total)),
                parents = this.$el.parents('.form-collection').length,
                heading = this.heading + (parents === 2 ? 3 : parents);

            form.find('fieldset:first').prepend(this.legend({"heading" : heading, "label" : this.options.label, "counter" : ++this.counter}));

            var div = $('<div />').addClass('child');

            if(parents !== 2) {
                div.addClass('well');
            }

            div.append(form);

            var view = new FormCollectionView({"el" : div}),
                el = view.render().el;

            $(l).before(el);

            var legend = $(el).find('.form-collection').siblings('legend'),
                head = $('<h' + (heading + 1) + ' />').html(legend.children().first().html());

            legend.html(head);

            return view;
        }
    });


    var FormCollectionView = Backbone.View.extend({

        "template" : _.template('<a class="pull-right remove-form" href="#"><%= icon %> <%=label%></a>'),
        "icon" : '<i class="icon-remove"></i> ',

        "render" : function(){

            var $this = this,
                template = this.template({"icon" : this.icon, "label" : ExposeTranslation.get('delete')}),
                tmpl = $(template).on("click", function(e){
                            e.preventDefault();
                            $this.destroy($this);
                });

            this.$el.prepend(tmpl);

            return this;
        },
        "destroy" : function($this) {

            $this.$el.remove();

            return this;
        }
    });



    function html_entity_decode (string, quote_style) {
          // http://kevin.vanzonneveld.net
          // +   original by: john (http://www.jd-tech.net)
          // +      input by: ger
          // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
          // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
          // +   bugfixed by: Onno Marsman
          // +   improved by: marc andreu
          // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
          // +      input by: Ratheous
          // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
          // +      input by: Nick Kolosov (http://sammy.ru)
          // +   bugfixed by: Fox
          // -    depends on: get_html_translation_table
          // *     example 1: html_entity_decode('Kevin &amp; van Zonneveld');
          // *     returns 1: 'Kevin & van Zonneveld'
          // *     example 2: html_entity_decode('&amp;lt;');
          // *     returns 2: '&lt;'
          var hash_map = {},
            symbol = '',
            tmp_str = '',
            entity = '';
          tmp_str = string.toString();

          if (false === (hash_map = get_html_translation_table('HTML_ENTITIES', quote_style))) {
            return false;
          }

          // fix &amp; problem
          // http://phpjs.org/functions/get_html_translation_table:416#comment_97660
          delete(hash_map['&']);
          hash_map['&'] = '&amp;';

          for (symbol in hash_map) {
            entity = hash_map[symbol];
            tmp_str = tmp_str.split(entity).join(symbol);
          }
          tmp_str = tmp_str.split('&#039;').join("'");

          return tmp_str;
        }


    function get_html_translation_table (table, quote_style) {
          // http://kevin.vanzonneveld.net
          // +   original by: Philip Peterson
          // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
          // +   bugfixed by: noname
          // +   bugfixed by: Alex
          // +   bugfixed by: Marco
          // +   bugfixed by: madipta
          // +   improved by: KELAN
          // +   improved by: Brett Zamir (http://brett-zamir.me)
          // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
          // +      input by: Frank Forte
          // +   bugfixed by: T.Wild
          // +      input by: Ratheous
          // %          note: It has been decided that we're not going to add global
          // %          note: dependencies to php.js, meaning the constants are not
          // %          note: real constants, but strings instead. Integers are also supported if someone
          // %          note: chooses to create the constants themselves.
          // *     example 1: get_html_translation_table('HTML_SPECIALCHARS');
          // *     returns 1: {'"': '&quot;', '&': '&amp;', '<': '&lt;', '>': '&gt;'}
          var entities = {},
            hash_map = {},
            decimal;
          var constMappingTable = {},
            constMappingQuoteStyle = {};
          var useTable = {},
            useQuoteStyle = {};

          // Translate arguments
          constMappingTable[0] = 'HTML_SPECIALCHARS';
          constMappingTable[1] = 'HTML_ENTITIES';
          constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
          constMappingQuoteStyle[2] = 'ENT_COMPAT';
          constMappingQuoteStyle[3] = 'ENT_QUOTES';

          useTable = !isNaN(table) ? constMappingTable[table] : table ? table.toUpperCase() : 'HTML_SPECIALCHARS';
          useQuoteStyle = !isNaN(quote_style) ? constMappingQuoteStyle[quote_style] : quote_style ? quote_style.toUpperCase() : 'ENT_COMPAT';

          if (useTable !== 'HTML_SPECIALCHARS' && useTable !== 'HTML_ENTITIES') {
            throw new Error("Table: " + useTable + ' not supported');
            // return false;
          }

          entities['38'] = '&amp;';
          if (useTable === 'HTML_ENTITIES') {
            entities['160'] = '&nbsp;';
            entities['161'] = '&iexcl;';
            entities['162'] = '&cent;';
            entities['163'] = '&pound;';
            entities['164'] = '&curren;';
            entities['165'] = '&yen;';
            entities['166'] = '&brvbar;';
            entities['167'] = '&sect;';
            entities['168'] = '&uml;';
            entities['169'] = '&copy;';
            entities['170'] = '&ordf;';
            entities['171'] = '&laquo;';
            entities['172'] = '&not;';
            entities['173'] = '&shy;';
            entities['174'] = '&reg;';
            entities['175'] = '&macr;';
            entities['176'] = '&deg;';
            entities['177'] = '&plusmn;';
            entities['178'] = '&sup2;';
            entities['179'] = '&sup3;';
            entities['180'] = '&acute;';
            entities['181'] = '&micro;';
            entities['182'] = '&para;';
            entities['183'] = '&middot;';
            entities['184'] = '&cedil;';
            entities['185'] = '&sup1;';
            entities['186'] = '&ordm;';
            entities['187'] = '&raquo;';
            entities['188'] = '&frac14;';
            entities['189'] = '&frac12;';
            entities['190'] = '&frac34;';
            entities['191'] = '&iquest;';
            entities['192'] = '&Agrave;';
            entities['193'] = '&Aacute;';
            entities['194'] = '&Acirc;';
            entities['195'] = '&Atilde;';
            entities['196'] = '&Auml;';
            entities['197'] = '&Aring;';
            entities['198'] = '&AElig;';
            entities['199'] = '&Ccedil;';
            entities['200'] = '&Egrave;';
            entities['201'] = '&Eacute;';
            entities['202'] = '&Ecirc;';
            entities['203'] = '&Euml;';
            entities['204'] = '&Igrave;';
            entities['205'] = '&Iacute;';
            entities['206'] = '&Icirc;';
            entities['207'] = '&Iuml;';
            entities['208'] = '&ETH;';
            entities['209'] = '&Ntilde;';
            entities['210'] = '&Ograve;';
            entities['211'] = '&Oacute;';
            entities['212'] = '&Ocirc;';
            entities['213'] = '&Otilde;';
            entities['214'] = '&Ouml;';
            entities['215'] = '&times;';
            entities['216'] = '&Oslash;';
            entities['217'] = '&Ugrave;';
            entities['218'] = '&Uacute;';
            entities['219'] = '&Ucirc;';
            entities['220'] = '&Uuml;';
            entities['221'] = '&Yacute;';
            entities['222'] = '&THORN;';
            entities['223'] = '&szlig;';
            entities['224'] = '&agrave;';
            entities['225'] = '&aacute;';
            entities['226'] = '&acirc;';
            entities['227'] = '&atilde;';
            entities['228'] = '&auml;';
            entities['229'] = '&aring;';
            entities['230'] = '&aelig;';
            entities['231'] = '&ccedil;';
            entities['232'] = '&egrave;';
            entities['233'] = '&eacute;';
            entities['234'] = '&ecirc;';
            entities['235'] = '&euml;';
            entities['236'] = '&igrave;';
            entities['237'] = '&iacute;';
            entities['238'] = '&icirc;';
            entities['239'] = '&iuml;';
            entities['240'] = '&eth;';
            entities['241'] = '&ntilde;';
            entities['242'] = '&ograve;';
            entities['243'] = '&oacute;';
            entities['244'] = '&ocirc;';
            entities['245'] = '&otilde;';
            entities['246'] = '&ouml;';
            entities['247'] = '&divide;';
            entities['248'] = '&oslash;';
            entities['249'] = '&ugrave;';
            entities['250'] = '&uacute;';
            entities['251'] = '&ucirc;';
            entities['252'] = '&uuml;';
            entities['253'] = '&yacute;';
            entities['254'] = '&thorn;';
            entities['255'] = '&yuml;';
          }

          if (useQuoteStyle !== 'ENT_NOQUOTES') {
            entities['34'] = '&quot;';
          }
          if (useQuoteStyle === 'ENT_QUOTES') {
            entities['39'] = '&#39;';
          }
          entities['60'] = '&lt;';
          entities['62'] = '&gt;';


          // ascii decimals to real symbols
          for (decimal in entities) {
            if (entities.hasOwnProperty(decimal)) {
              hash_map[String.fromCharCode(decimal)] = entities[decimal];
            }
          }

          return hash_map;
        }

    $.fn.formCollection = function(options) {

        $(this).each(function(){
            var view = new MasterView($.extend({"el" : this}, options));
        });

        return this;
    };

})(window.jQuery, Backbone, _);