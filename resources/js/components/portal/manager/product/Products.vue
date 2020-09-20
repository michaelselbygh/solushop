<template>
    <div class="table-responsive">
        <table ref="products" id="order-records" class="table table-striped table-bordered zero-configuration">
            <thead>
                <tr>
                    <th>ID</th>
                    <th style="min-width: 100px">SKUs</th>
                    <th>Preview</th>
                    <th style="min-width: 230px">Name</th>
                    <th>State</th>
                    <th style="min-width: 100px;">Sold By</th>
                    <th>Main Phone</th>
                    <th>Alt. Phone</th>
                    <th style="min-width: 140px">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="record in records" v-bind:key="record.id">
                    <td>
                        {{ record.id }}
                    </td>
                    <td>
                        {{ variations(record.skus) }}
                    </td>
                    <td class="text-truncate p-1">
                        <ul class="list-unstyled users-list m-0">
                            <li data-toggle="tooltip" data-popup="tooltip-custom" :data-original-title="record.name" class="avatar avatar-sm pull-up">
                                <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius"
                                :src="'https://www.solushop.com.gh/app/assets/img/products/thumbnails/'+record.images[0].pi_path+'.jpg'"
                                :alt="record.name">
                            </li>
                        </ul>
                    </td>
                    <td>
                        {{ record.product_name }}
                    </td>
                    <td v-html="record.state.ps_html"></td>
                    <td>
                        {{ record.vendor.name }}
                    </td>
                    <td>
                        {{ "0" + record.vendor.phone.substr(3) }}
                    </td>
                    <td>
                        {{ "0" + record.vendor.alt_phone.substr(3) }}
                    </td>
                    <td >
                        <a target="new" :href="'https://www.solushop.com.gh/portal/manager/product/'+record.id">
                            <button class="btn btn-info btn-sm round">
                                <i class="ft-eye"></i>
                            </button>
                        </a>
                        <button @click="triggerAction('approve', record.id)" v-if="record.state.id == 2 || record.state.id == 3 || record.state.id == 5" class="btn btn-success btn-sm round">
                            <i class="ft-check"></i>
                        </button>
                        <button @click="triggerAction('disapprove', record.id)" v-if="record.state.id == 1" class="btn btn-warning btn-sm round">
                            <i class="ft-alert-triangle"></i>
                        </button>
                        <button @click="triggerAction('reject', record.id)" v-if="record.state.id == 2" class="btn btn-warning btn-sm round">
                            <i class="ft-x"></i>
                        </button>
                        <button @click="triggerAction('delete', record.id)" class="btn btn-danger btn-sm round">
                            <i class="ft-trash"></i>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>     
</template>

<script>
export default {
    props: ['records'],

    mounted() {
        this.dt = $(this.$refs.products).DataTable();
    },
    watch: {
        records(val) {
            this.dt.destroy();
            this.$nextTick(() => {
                this.dt = $(this.$refs.products).DataTable({
                    "order": [
                        [3, 'asc']
                    ],
                    "drawCallback": function() {
                        $('[data-toggle="tooltip"]').tooltip();
                    },
                });
                $('[data-toggle="tooltip"]').tooltip();
            });
        }
    },

    methods: {
        variations(skus){
            var variations = "";
            variations = skus[0].sku_variant_description + " ";
            if (skus.length > 1) {
                for (let i = 1; i < skus.length; i++) {
                    variations = variations + ", " + skus[i].sku_variant_description;
                }
            }

            return variations;
        },

        async triggerAction(action, id){
            this.$root.$emit("action", {action: action, id: id});

        }
    }
}



</script>