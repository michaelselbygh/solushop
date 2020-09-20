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
                        <div class="col-md-8">
                            <transactions v-if="transactions" :transactions="transactions" :loading="loading"></transactions>
                        </div>
                        <div class="col-md-4">
                            <balances v-if="count.balances" :balances="count.balances"></balances>
                            <pay :pay="pay" :loading="loading" @recordPayment="recordAccountsPayment"></pay>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
</template>

<script>
import Transactions from './Transactions.vue';
import Balances from './Balances.vue';
import Pay from './Pay.vue';

export default {
    components: {
        'transactions' : Transactions,
        'balances' : Balances,
        'pay' : Pay
    },
    data() {
        return{
            transactions: null,
            loading:{
                main: true,
                transactions: false,
                pay: false
            },
            count: {
                transactions: {
                    old: 0,
                    new: 0
                },
                balances: null
            },
            pay:{
                action: "Pay-In",
                amount: 0,
                description: ""
            },
            base_url: window.location.origin,
        }
    },
    async mounted(){
        await this.updateDetails();
        this.loading.main = false;
        setInterval(this.updateCounts, 10000);
    },

    methods: {
        async updateDetails(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/accounts/records',
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
            this.transactions = response.data.records;
            this.setCounts(response.data);
        },

        async updateCounts(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/accounts/count',
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

            //check change in transactions data
            if (this.count.transactions.new != this.count.transactions.old) {
                this.loading.transactions = true;

                //update old counts
                this.count.transactions.old = this.count.transactions.new;
                var response = await this.getRecords();
                this.transactions = response.data.records;
                this.loading.transactions = false;
            }
        },
        
        setCounts(data){

            if(this.count.transactions.old == 0){
                //set old counts
                this.count.transactions.old = data.count;
                this.count.balances = data.balances;
            }

            //update new counts
            this.count.transactions.new = data.count;
            this.count.balances = data.balances;
        },

        getRecords(){
            return axios({
                        method: 'post',
                        url: 'https://www.solushop.com.gh/portal/manager/accounts/records',
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

        async recordAccountsPayment(){
            this.loading.pay = true;
            var response = await axios({
                method: 'post',
                url: 'https://www.solushop.com.gh/portal/manager/accounts/record-payment',
                data: {
                    type: this.pay.action,
                    amount: this.pay.amount,
                    description: this.pay.description
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
            }

            this.updateRecords;
            this.pay.action = "Pay-Out";
            this.pay.amount = 0;
            this.pay.description = "";
            this.loading.pay = false;

        }
    }
}
</script>