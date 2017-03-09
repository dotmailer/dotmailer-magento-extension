var ConnectorProductSelectorForm = new Class.create();

ConnectorProductSelectorForm.prototype = {
    initialize: function (a) {
        this.updateElement = $(a);
        this.chooserSelectedItems = $H({});
    },

    chooserGridRowInit: function (a) {
        if (!a.reloadParams) {
            var c = this.updateElement.value.split(","),
                d = "";
            for (i = 0; i < c.length; i++) {
                d = c[i].strip();
                if (d != "") {
                    this.chooserSelectedItems.set(d, 1)
                }
            }

            a.reloadParams = {
                "selected[]": this.chooserSelectedItems.keys()
            }
        }
    },

    //trigger checkbox checked/unchecked upon clicking anywhere on the row
    chooserGridRowClick: function (b, d) {
        var f = Event.findElement(d, "tr");
        var a = Event.element(d).tagName == "INPUT";
        if (f) {
            var e = Element.select(f, "input");
            if (e[0]) {
                var c = a ? e[0].checked : !e[0].checked;
                b.setCheckboxChecked(e[0], c)
            }
        }
    },

    //checkbox checked/unchecked event handler and update values
    chooserGridCheckboxCheck: function (b, a, c) {
        if (c) {
            if (!a.up("th")) {
                this.chooserSelectedItems.set(a.value, 1)
            }
        } else {
            this.chooserSelectedItems.unset(a.value);
        }

        b.reloadParams = {
            "selected[]": this.chooserSelectedItems.keys()
        };
        this.updateElement.value = this.chooserSelectedItems.keys().join(",")
    }
};