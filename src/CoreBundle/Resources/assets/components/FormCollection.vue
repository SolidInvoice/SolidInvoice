<script>
    export default {
        props: {
            allowDelete: {
                type: Boolean,
                required: false,
                default: false
            }
        },
        data() {
            return {
                items: []
            }
        },
        methods: {
            addRow() {
                this.items.push(this.getRow());
            },
            getRow() {
                return this.$scopedSlots.prototype({index: this.items.length});
            }
        },
        mounted: function() {
            this.addRow();
        },
        render(createElement) {
            return createElement('span', this.items);
        }
    }
</script>