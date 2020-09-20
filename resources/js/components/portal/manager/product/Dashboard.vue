<template>
    <div>
        <div class="app-content content">
            <transition name="fade" mode="out-in">
                <div  v-if="loading.main"  class="preloader-wrap">
                    <div class=" custom-center">
                        <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                    </div>
                    
                </div>
            </transition>
            <div class="content-wrapper">
                <div class="content-header row">
                </div>
                <div class="content-body">
                    <div>
                        <div>
                            <div class="row">
                                <product-count @clicked="updateFilter" ref="pending" cc="warning" label="Pending"></product-count>
                                <product-count @clicked="updateFilter" ref="live" cc="success" label="Live"></product-count>
                                <product-count @clicked="updateFilter" ref="rejected" cc="danger" label="Rejected"></product-count>
                                <product-count @clicked="updateFilter" ref="inactive" cc="dark" label="Inactive"></product-count>
                            </div>
                            <div class="row">
                                <div id="recent-transactions" class="col-md-12">
                                    <div class="card" style="min-height: 550px">
                                        <div class="card-content">
                                            <div class="card-body card-dashboard">
                                                <transition name="fade" mode="out-in">
                                                    <div  v-if="loading.products"  class="div-preloader-wrap">
                                                        <div class=" custom-center">
                                                            <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                                                        </div>
                                                    </div>
                                                </transition>
                                                <products @action="action()" :loading="loading" :records="records"></products>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</template>

<script>
import Products from './Products.vue';
import ProductCount from './ProductCount.vue';

export default {
    data: function () {
        return { 
            filter: 'Pending',
            loading: {
                main: true,
                products: false
            },
            count: {
                pending: {
                    old: 0,
                    new: 0
                },
                live: {
                    old: 0,
                    new: 0
                },
                rejected: {
                    old: 0,
                    new: 0
                },
                inactive:{
                    old: 0,
                    new: 0
                },
                total: {
                    old: 0,
                    new: 0
                }
            },
            records: [],
            base_url: window.location.origin,
        }
    },

    components: {
        'products' : Products,
        'product-count' : ProductCount
    },

    async mounted(){
        await this.updateRecords(this.filter);
        this.loading.main = false;
        setInterval(this.updateCounts, 10000);

        this.$root.$on('action',function(value){
           this.$children[0].productAction(value);

        });
    },

    methods: {
        setCounts(response){
            //setting counts on dashboard
            this.$refs.pending.order.count = response.data.pending;
            this.$refs.live.order.count = response.data.live;
            this.$refs.rejected.order.count = response.data.rejected;
            this.$refs.inactive.order.count = response.data.inactive;

            if(this.count.total.old == 0){
                //set old counts
                this.count.pending.old = response.data.pending;
                this.count.live.old = response.data.live;
                this.count.rejected.old = response.data.rejected;
                this.count.inactive.old = response.data.inactive;
                this.count.total.old = response.data.total;
            }

            //update new counts
            this.count.pending.new = response.data.pending;
            this.count.live.new = response.data.live;
            this.count.rejected.new = response.data.rejected;
            this.count.inactive.new = response.data.inactive;
            this.count.total.new = response.data.total;
        },

        async updateCounts(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/products/count',
                                data: {
                                    pending: [2],
                                    live: [1],
                                    rejected: [3],
                                    inactive: [5]
                                }
                            })
                            .catch(error => {
                                switch (error.response.status) {
                                    case 401:
                                        this.$toast.question("User logged out. Kindly log back in.", '', {
                                            timeout: 3000,
                                            close: false,
                                            overlay: true,
                                            displayMode: 'once',
                                            id: 'question',
                                            zindex: 999,
                                            position: 'center',
                                            onClosing: function(instance, toast, closedBy){
                                                window.location.href = "https://www.solushop.com.gh/portal/manager/login";
                                            }
                                        });
                                        break;

                                    case 419:
                                        this.$toast.question('Session expired. Refreshing automatically in 10 seconds', '', {
                                            timeout: 10000,
                                            close: false,
                                            overlay: true,
                                            displayMode: 'once',
                                            id: 'question',
                                            zindex: 999,
                                            position: 'center',
                                            onClosing: function(instance, toast, closedBy){
                                                document.location.reload();
                                            }
                                        });
                                        

                                        break;
                                
                                    default:
                                        break;
                                }
                            });
                            
            this.setCounts(response);
            if (this.checkCountChange()) {
                this.loading.products = true;

                //update old counts
                this.count.pending.old = this.count.pending.new;
                this.count.live.old = this.count.live.new;
                this.count.rejected.old = this.count.rejected.new;
                this.count.inactive.old = this.count.inactive.new;
                this.count.total.old = this.count.total.new;

                await this.updateRecords(this.filter);
                this.loading.products = false;
            }
        },

        async updateRecords(type){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/products/records',
                                data: {
                                    type: type
                                }
                            })
                            .catch(error => {
                                switch (error.response.status) {
                                    case 401:
                                        this.$toast.question("User logged out. Kindly log back in.", '', {
                                            timeout: 3000,
                                            close: false,
                                            overlay: true,
                                            displayMode: 'once',
                                            id: 'question',
                                            zindex: 999,
                                            position: 'center',
                                            onClosing: function(instance, toast, closedBy){
                                                window.location.href = "https://www.solushop.com.gh/portal/manager/login";
                                            }
                                        });
                                        break;

                                    case 419:
                                        this.$toast.question('Session expired. Refreshing automatically in 10 seconds', '', {
                                            timeout: 10000,
                                            close: false,
                                            overlay: true,
                                            displayMode: 'once',
                                            id: 'question',
                                            zindex: 999,
                                            position: 'center',
                                            onClosing: function(instance, toast, closedBy){
                                                document.location.reload();
                                            }
                                        });
                                        

                                        break;
                                
                                    default:
                                        break;
                                }
                            });
            this.records = response.data.records;
            this.setCounts(response);
        },

        checkCountChange(){
            if (this.count.pending.new != this.count.pending.old || this.count.live.new != this.count.live.old || this.count.rejected.new != this.count.rejected.old || this.count.inactive.new != this.count.inactive.old) {
                return true;
            }else{
                return false;
            }
        },

        async updateFilter(fv){
            this.loading.products = true;
            this.filter = fv;
            await this.updateRecords(this.filter);
            this.loading.products = false;
        },

        async productAction(value){
            this.loading.products = true;
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/products/action',
                                data: {
                                    type: this.filter,
                                    id: value.id,
                                    action: value.action
                                }
                            })
                            .catch(error => {
                                switch (error.response.status) {
                                    case 401:
                                        this.$toast.question("User logged out. Kindly log back in.", '', {
                                            timeout: 3000,
                                            close: false,
                                            overlay: true,
                                            displayMode: 'once',
                                            id: 'question',
                                            zindex: 999,
                                            position: 'center',
                                            onClosing: function(instance, toast, closedBy){
                                                window.location.href = "https://www.solushop.com.gh/portal/manager/login";
                                            }
                                        });
                                        break;

                                    case 419:
                                        this.$toast.question('Session expired. Refreshing automatically in 10 seconds', '', {
                                            timeout: 10000,
                                            close: false,
                                            overlay: true,
                                            displayMode: 'once',
                                            id: 'question',
                                            zindex: 999,
                                            position: 'center',
                                            onClosing: function(instance, toast, closedBy){
                                                document.location.reload();
                                            }
                                        });
                                        

                                        break;
                                
                                    default:
                                        break;
                                }
                            });

            if (response.data.type == "error") {
                //sort 
                response.data.message.sort(function(a, b){
                    return a.length - b.length;
                });
                this.loading.products = false;

                //show error message(s)
                for (let i = 0; i < response.data.message.length ; i++) {
                    this.$toast.error(response.data.message[i], "", {
                        timeout: 5000,
                    });
                }
            } else {
                //update old counts
                this.count.pending.old = this.$refs.pending.order.count = response.data.pending;
                this.count.live.old =  this.$refs.live.order.count = response.data.live;
                this.count.rejected.old = this.$refs.rejected.order.count = response.data.rejected;
                this.count.inactive.old = this.$refs.inactive.order.count = response.data.inactive;
                this.count.total.old  = response.data.total;

                this.records = response.data.records;
                this.loading.products = false;

                //show success message
                this.$toast.success(response.data.message, "", {
                    timeout: 5000,
                });
            }

            
        }
    }
}
</script>