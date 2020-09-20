<template>
    <div>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="card-title">Coupons</h5>
                            <div class="card" style="min-height: 615px;">
                                <transition name="fade" mode="out-in">
                                    <div  v-if="loading"  class="div-preloader-wrap">
                                        <div class=" custom-center">
                                            <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                                        </div>
                                    </div>
                                </transition>
                                <div class="card-content collapse show">
                                    <div class="card-body card-dashboard">
                                        <table ref="coupons" id="coupon-records" class="table table-striped table-bordered zero-configuration">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th style="min-width: 170px;">Code</th>
                                                    <th>Value (GH¢)</th>
                                                    <th>Owner</th>
                                                    <th>Expiry Date</th>
                                                    <th>State</th>
                                                    <th>Created On</th>
                                                </tr>
                                            </thead>
                                            <tbody> 
                                                    <tr v-for="coupon in coupons" :key="coupon.id"> 
                                                        <td>{{ coupon.id }}</td>
                                                        <td>{{ coupon.coupon_code }}</td>
                                                        <td>{{ coupon.coupon_value }}</td>
                                                        <td>{{ coupon.coupon_owner }}</td>
                                                        <td>{{ coupon.coupon_expiry_date }}</td>
                                                        <td v-html="coupon.state.cs_state_html"></td>
                                                        <td>{{ coupon.created_at }}</td>
                                                    </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    data(){
        return {
            coupons: null,
            loading: true,
            count: {
                old: {
                    na: 0,
                    available: 0,
                    redeemed: 0,
                    expired: 0
                },
                new: {
                    na: 0,
                    available: 0,
                    redeemed: 0,
                    expired: 0
                }
            },
            dt: null,
            base_url: window.location.origin,
        }
    },

    async mounted(){
        this.dt = $(this.$refs.coupons).DataTable();
        await this.updateRecords(this.filter);
        this.loading = false;
        setInterval(this.updateCounts, 10000);
    },


    methods: {
        setCount(count){
            if (this.count.old.na == 0) {
                this.count.old.na = count.na;
                this.count.old.available = count.available;
                this.count.old.redeemed = count.redeemed;
                this.count.old.expired = count.expired;
            }
            this.count.new.na = count.na;
            this.count.new.available = count.available;
            this.count.new.redeemed = count.redeemed;
            this.count.new.expired = count.expired;
        },

        async updateCounts(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/coupons/count',
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

            this.setCount(response.data);
            if (this.change()) {
                this.loading = true;

                //update old counts
                this.count.old.na = this.count.new.na;
                this.count.old.available = this.count.new.available;
                this.count.old.redeemed = this.count.new.redeemed;
                this.count.old.expired = this.count.new.expired;

                await this.updateRecords();
                this.loading = false;
            }
        },

        async updateRecords(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/coupons/records',
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
            this.coupons = response.data.records;
            this.setCount(response.data);
        },

        change(){
            if (this.count.old.na != this.count.new.na || this.count.old.available != this.count.new.available || this.count.old.redeemed != this.count.new.redeemed || this.count.old.expired != this.count.new.expired ) {
                return true;
            }
            return false;
        }
    },
    
    watch: {
        coupons(val) {
            this.dt.destroy();
            this.$nextTick(() => {
                this.dt = $(this.$refs.coupons).DataTable({
                    "order": [
                        [5, 'asc']
                    ],
                    "drawCallback": function() {
                        $('[data-toggle="tooltip"]').tooltip();
                    },
                });
                $('[data-toggle="tooltip"]').tooltip();
            });
        }
    }
}
</script>