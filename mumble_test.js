!function () {
    function a() {
        $.getScript("//cdnjs.cloudflare.com/ajax/libs/knockout/3.1.0/knockout-min.js", function () {
            console.log("Loading knockout.js..."), b()
        }), c = window.jQuery.noConflict(!0), c.ajaxSetup({async: !1})
    }

    function b() {
        c(document).ready(function (a) {
            function b() {
                var b = this;
                b.cvp = ko.observable(), b.userCount = ko.observable();
                var c = function () {
                    a.ajax({
                        url: j, async: !0, dataType: "jsonp", jsonpCallback: "callback", success: function (a) {
                            console.log(a), b.cvp(a), b.userCount(d(a))
                        }, error: function (a) {
                            console.log(a)
                        }
                    })
                }, d = function (a) {
                    var b = a.root.users.length, c = function (a) {
                        for (var d in a)if (a.hasOwnProperty(d) && "object" == typeof a[d]) {
                            if ("channels" === d)for (var e = 0; e < a[d].length; e++) {
                                var f = a[d][e].users.length;
                                b += f
                            }
                            c(a[d])
                        }
                    };
                    return c(a.root), b
                };
                setInterval(function () {
                    c()
                }, 15e3), b.cvp(c())
            }

            var c = a(".mumble-widget"), d = c.data("key"), e = c.data("width") || 500, f = c.data("source") || "//guildbit.com/server/cvp/" + d + "/json/?callback=?", g = c.data("theme") || "default", h = "//dqc3ygqu0f1ud.cloudfront.net/dist/mumble-widget/mumble-widget.min.css";
            a("#mumble-widget-container").width(e);
            var i = a("<link>", {rel: "stylesheet", type: "text/css", href: h});
            i.appendTo("head");
            var j = f, k = "<table class='mumble-script-widget rounded centered' data-bind='with: cvp'><thead>                 <tr data-bind='if: $data.root'><th><a href='#' data-bind='text: name, attr: { href: x_connecturl }'></a></th></tr>                 <tr data-bind='ifnot: $data.root'><th>Not Found</th></tr>                 </thead><tbody>                 <!-- ko if: $data.root -->                   <!-- ko foreach: root.users -->                   <tr class='root-users'><td data-bind='text: name'></td></tr>                   <!-- /ko -->                   <!-- ko foreach: root.channels -->                     <!-- ko if: users.length > 0 -->                     <tr class='subchannels'><td data-bind='text: name'></td></tr>                     <!-- /ko -->                     <!-- ko foreach: users -->                     <tr class='sub-users'><td data-bind='text: &apos;&mdash; &apos; + name'></td></tr>                     <!-- /ko -->                     <!-- ko template: {name: 'subchannels_template', foreach: $data.channels} -->                     <!-- /ko -->                   <!-- /ko -->                   <!-- ko if: $root.userCount() == 0 -->                   <tr><td>No users are online</td></tr>                   <!-- /ko -->                 <!-- /ko -->                                 <!-- ko ifnot: $data.root -->                 <tr><td>Unable to load</td></tr>                 <!-- /ko -->                                 </tbody></table>                 <script id='subchannels_template' type='text/html'>                         <tr class='subchannels'><td data-bind='text: &apos;&mdash; &apos; + name, visible: users.length > 0'></td></tr>                         <!-- ko foreach: users -->                         <tr class='sub-users'><td data-bind='text: &apos;&mdash; &apos; + name'></td></tr>                         <!-- /ko -->                         <!-- ko template: {name: 'subchannels_template', foreach: $data.channels} -->                         <!-- /ko -->                 </script>";
            a("#mumble-widget-container").html(k).addClass(g), ko.applyBindings(new b)
        })
    }

    var c;
    if (void 0 === window.jQuery || "2.1.1" !== window.jQuery.fn.jquery) {
        var d = document.createElement("script");
        d.setAttribute("type", "text/javascript"), d.setAttribute("src", "//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.1/jquery.min.js"), d.readyState ? d.onreadystatechange = function () {
            ("complete" === this.readyState || "loaded" === this.readyState) && a()
        } : d.onload = a, (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(d)
    } else c = window.jQuery, b()
}();