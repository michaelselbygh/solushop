<template>
    <div>
        <h5>Record Payment</h5>
        <div class="card">
            <transition name="fade" mode="out-in">
                <div  v-if="loading.pay"  class="div-preloader-wrap">
                    <div class=" custom-center">
                        <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                    </div>
                </div>
            </transition>
            <div class="card-content collapse show">
                <div class="card-body card-dashboard">
                    <form>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Type</label>
                                    <select class="form-control" v-model="pay.action" style='border-radius:7px;' required>
                                        <option>Pay-Out</option>
                                        <option>Pay-In</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" >
                                <div class="form-group">
                                    <label>Amount</label>
                                    <input class="form-control round"  v-model="pay.amount" type="number" step="0.01" required> 
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="payment_description">Description</label>
                            <input class="form-control round" v-model="pay.description" placeholder="Describe the payment." value="" type="text"> 
                        </div>
                        <div class="form-actions" style="text-align:center; padding: 0" >
                            <button @click.prevent="triggerAccountsPayment()"  class="btn btn-success">
                                    Record {{ pay.action }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['pay', 'loading'],
    methods: {
        triggerAccountsPayment(){
            this.$emit("recordPayment", "");
        }
    }
}
</script>