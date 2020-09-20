<template>
    <div>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="card-title">Subscriptions</h5>
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
                                        <table ref="subscriptions" id="subscription-records" class="table table-striped table-bordered zero-configuration">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th style="min-width: 250px;">Vendor</th>
                                                    <th>Phone</th>
                                                    <th>Package</th>
                                                    <th>Status</th>
                                                    <th>Days Left</th>
                                                    <th>Created</th>
                                                    <th>Updated</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody> 
                                                    <tr v-for="subscription in subscriptions" :key="subscription.subscription_id"> 
                                                        <td>{{ subscription.subscription_id }}</td>
                                                        <td>{{ subscription.name }}</td>
                                                        <td>{{ "0"+subscription.phone.substr(3) }}</td>
                                                        <td>{{ subscription.vs_package_description }}</td>
                                                        <td v-html="status(subscription.vs_days_left)"></td>
                                                        <td>{{ subscription.vs_days_left }}</td>
                                                        <td>{{ subscription.subscription_created_at }}</td>
                                                        <td>{{ subscription.subscription_updated_at }}</td>
                                                        <td>
                                                            <a :href="'https://www.solushop.com.ghportal/manager/vendor/' + subscription.username">
                                                                <button data-toggle="tooltip" data-popup="tooltip-custom" :data-original-title="`View ` + subscription.name "  class="btn btn-info btn-sm round">
                                                                    <i class="ft-eye"></i>
                                                                </button>
                                                            </a>
                                                            <button v-if="subscription.vs_days_left > 0" @click="cancel(subscription.subscription_id)" data-toggle="tooltip" data-popup="tooltip-custom" :data-original-title="`Cancel ` + subscription.name + `'s Subscription`" class="btn btn-danger btn-sm round">
                                                                <i class="ft-x"></i>
                                                            </button>
                                                        </td>
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
            subscriptions: null,
            loading: true,
            count: {
                old: 0,
                new: 0
            },
            dt: null,
            base_url: window.location.origin,
        }
    },

    async mounted(){
        this.dt = $(this.$refs.subscriptions).DataTable();
        await this.updateRecords(this.filter);
        this.loading = false;
        setInterval(this.updateCounts, 10000);
    },


    methods: {
        setCount(count){
            if (this.count.old == 0) {
                this.count.old = count;
            }
            this.count.new = count;
        },

        async updateCounts(){
            var countResponse = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/subscriptions/count',
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

            this.setCount(countResponse.data.count);
            if (this.count.old != this.count.new) {
                this.loading = true;

                //update old counts
                this.count.old = this.count.new;

                await this.updateRecords();
                this.loading = false;
            }
        },

        async updateRecords(){
            var recordsResponse = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/subscriptions/records',
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
            this.subscriptions = recordsResponse.data.records;
            this.setCount(recordsResponse.data.count);
        },

        status(days){
            if (days > 0) {
                return "<span style='font-weight: 450; color: green'>Active</span>";
            }

            return "<span style='font-weight: 450; color: red'>Expired</span>";
        },

        async cancel(id){
            this.loading = true;
            var response = await axios({
                method: 'post',
                url: 'https://www.solushop.com.gh/portal/manager/subscriptions/cancel',
                data: {
                    id: id
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
            this.loading = false; 
            if (response.data.type == "error") {
                //sort 
                response.data.message.sort();
                //show error message(s)
                for (let i = response.data.message.length - 1; i >= 0 ; i--) {
                    this.$toast.error(response.data.message[i], "", {
                        timeout: 5000,
                    });
                }
            } else {
                //show success message
                this.$toast.success(response.data.message, "", {
                    timeout: 5000,
                });
            }
            
        }
    },
    
    watch: {
        subscriptions(val) {
            this.dt.destroy();
            this.$nextTick(() => {
                this.dt = $(this.$refs.subscriptions).DataTable({
                    "order": [
                        [4, 'asc'],
                        [1, 'asc'],
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