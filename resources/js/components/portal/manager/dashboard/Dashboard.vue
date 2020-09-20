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
                                <order-count @clicked="updateFilter" ref="new" cc="info" label="New"></order-count>
                                <order-count @clicked="updateFilter" ref="ongoing" cc="warning" label="Ongoing"></order-count>
                                <order-count @clicked="updateFilter" ref="completed" cc="success" label="Completed"></order-count>
                                <order-count @clicked="updateFilter" ref="unpaid" cc="dark" label="Unpaid"></order-count>
                            </div>
                            <div class="row">
                                <div id="recent-transactions" class="col-md-12">
                                    <div class="card" style="min-height: 550px">
                                        <div class="card-content">
                                            <div class="card-body card-dashboard">
                                                <transition name="fade" mode="out-in">
                                                    <div  v-if="loading.orders"  class="div-preloader-wrap">
                                                        <div class=" custom-center">
                                                            <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                                                        </div>
                                                    </div>
                                                </transition>
                                                <orders :records="records"></orders>
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
import Orders from './Orders.vue';
import OrderCount from './OrderCount.vue';

export default {
    data: function () {
        return { 
            filter: 'New',
            loading: {
                main: true,
                orders: false
            },
            count: {
                new: {
                    old: 0,
                    new: 0
                },
                ongoing: {
                    old: 0,
                    new: 0
                },
                completed: {
                    old: 0,
                    new: 0
                },
                unpaid:{
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
        'orders' : Orders,
        'order-count' : OrderCount
    },

    async mounted(){
        await this.updateRecords(this.filter);
        this.loading.main = false;
        setInterval(this.updateCounts, 10000);
    },

    methods: {
        setCounts(response){
            //setting counts on dashboard
            this.$refs.new.order.count = response.data.new;
            this.$refs.ongoing.order.count = response.data.ongoing;
            this.$refs.completed.order.count = response.data.completed;
            this.$refs.unpaid.order.count = response.data.unpaid;

            if(this.count.total.old == 0){
                //set old counts
                this.count.new.old = response.data.new;
                this.count.ongoing.old = response.data.ongoing;
                this.count.completed.old = response.data.completed;
                this.count.unpaid.old = response.data.unpaid;
                this.count.total.old = response.data.total;
            }

            //update new counts
            this.count.new.new = response.data.new;
            this.count.ongoing.new = response.data.ongoing;
            this.count.completed.new = response.data.completed;
            this.count.unpaid.new = response.data.unpaid;
            this.count.total.new = response.data.total;
        },

        async updateCounts(){
            var countUpdate = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/orders/count',
                                data: {
                                    new: [2],
                                    ongoing: [3, 4, 5],
                                    completed: [6],
                                    unpaid: [1]
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
                            
            this.setCounts(countUpdate);
            if (this.checkCountChange()) {
                this.loading.orders = true;

                //update old counts
                this.count.new.old = this.count.new.new;
                this.count.ongoing.old = this.count.ongoing.new;
                this.count.completed.old = this.count.completed.new;
                this.count.unpaid.old = this.count.unpaid.new;
                this.count.total.old = this.count.total.new;

                await this.updateRecords(this.filter);
                this.loading.orders = false;
            }
        },

        async updateRecords(type){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/orders/records',
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
            if (this.count.new.new != this.count.new.old || this.count.ongoing.new != this.count.ongoing.old || this.count.completed.new != this.count.completed.old || this.count.unpaid.new != this.count.unpaid.old || this.count.new.new != this.count.new.old) {
                return true;
            }else{
                return false;
            }
        },

        async updateFilter(fv){
            this.loading.orders = true;
            this.filter = fv;
            await this.updateRecords(this.filter);
            this.loading.orders = false;
        }
    }
}
</script>