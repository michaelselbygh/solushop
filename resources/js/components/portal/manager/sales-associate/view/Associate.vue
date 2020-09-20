<template>
    <div>
        <transition name="fade" mode="out-in">
            <div  v-if="loading.main"  class="preloader-wrap">
                <div class=" custom-center">
                    <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                </div>
            </div>
        </transition>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-5">
                            <edit v-if="associate" @updateDetails="updateAssociateDetails"  :associate="associate" :loading="loading"></edit>
                        </div>
                        <div class="col-md-3">
                            <pay v-if="associate" @payAssociate="recordAssociatePayment"  :pay="pay" :associate="associate" :loading="loading"></pay>
                            <badge v-if="associate" :associate="associate" :loading="loading"></badge>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <transactions v-if="associate" :associate="associate" :loading="loading" :transactions="transactions"></transactions>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script>
import Badge from './Badge.vue'
import Edit from './Edit.vue';
import Pay from './Pay.vue';
import Transactions from './Transactions.vue';

export default {
    components: {
        'badge' : Badge,
        'edit' : Edit,
        'pay' : Pay,
        'transactions' : Transactions
    },
    props: ['id'],
    data() {
        return{
            associate: null,
            transactions: null,
            loading:{
                main: true,
                details: false,
                pay: false,
                transactions: false
            },
            count: {
                updated: {
                    old: null,
                    new: null
                },
                transactions: {
                    old: 0,
                    new: 0
                }
            },
            pay:{
                amount: 0
            },
            base_url: window.location.origin,
        }
    },
    async mounted(){
        await this.updateDetails();
        document.title = "Associate, " + this.associate.first_name + " " + this.associate.last_name;
        this.pay.amount = this.associate.balance.toFixed(2);
        this.loading.main = false;
        setInterval(this.updateCounts, 10000);
    },
    methods: {
        async updateDetails(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/sales-associate/records',
                                data: {
                                    id : this.id
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
            this.associate = response.data.records;
            this.transactions = response.data.transactions;
            this.setCounts(response.data);
        },

        async updateCounts(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/sales-associate/count',
                                data: {
                                    id : this.id
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
                            
            this.setCounts(response.data);

            //check change in associate data
            if (this.count.updated.new != this.count.updated.old) {
                this.loading.details = true;

                //update old counts
                this.count.updated.old = this.count.updated.new;
                var response = await this.getRecords();
                this.associate = response.data.records;
                this.loading.details = false;
            }

            //check change in associate transactions
            if (this.count.transactions.new != this.count.transactions.old) {
                this.loading.transactions = true;

                //update old counts
                this.count.transactions.old = this.count.transactions.new;

                var response = await this.getRecords();
                this.transactions = response.data.transactions;
                this.loading.transactions = false;
            }

        },
        
        setCounts(data){

            if(this.count.updated.old == null){
                //set old counts
                this.count.updated.old = data.count.updated;
                this.count.transactions.old = data.count.transactions;
            }

            //update new counts
            this.count.updated.new = data.count.updated;
            this.count.transactions.new = data.count.transactions;
        },

        getRecords(){
            return axios({
                        method: 'post',
                        url: 'https://www.solushop.com.gh/portal/manager/sales-associate/records',
                        data: {
                            id : this.id
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

        },

        async updateAssociateDetails(){
            this.loading.details = true;
            var response = await axios({
                method: 'post',
                url: 'https://www.solushop.com.gh/portal/manager/sales-associate/update-records',
                data: {
                    id : this.id,
                    first_name: this.associate.first_name,
                    last_name: this.associate.last_name,
                    phone: this.associate.phone,
                    email: this.associate.email,
                    mode_of_payment: this.associate.mode_of_payment,
                    payment_details: this.associate.payment_details,
                    address: this.associate.address,
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
            this.count.updated.old = response.data.updated;
            this.loading.details = false;
        },

        async recordAssociatePayment(){
            this.loading.pay = true;
            var response = await axios({
                method: 'post',
                url: 'https://www.solushop.com.gh/portal/manager/sales-associate/record-payment',
                data: {
                    id : this.id,
                    amount: this.pay.amount
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
                //show error message(s)
                for (let i = 0; i < response.data.message.length ; i++) {
                    this.$toast.error(response.data.message[i], "", {
                        timeout: 5000,
                    });
                }
            } else {
                //show success message
                this.$toast.success(response.data.message, "", {
                    timeout: 5000,
                });

                this.associate = response.data.records;
                this.count.updated.old = response.data.count.updated;
                this.pay.amount = this.associate.balance.toFixed(2);
                
            }

            this.loading.pay = false;
        }

    }
}
</script>