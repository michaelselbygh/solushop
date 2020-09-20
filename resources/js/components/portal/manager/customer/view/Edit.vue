<template>
    <div>
        <h5 class="card-title">
            <div class="row">
                <div class="col-md-7">
                    Edit {{ customer.first_name }}'s details 
                </div>
                <div class="col-md-5" style="text-align: right;">
                    ID: <b>{{ customer.id }}</b>
                </div>
            </div>
        </h5>
        <div class="card" style="">
            <transition name="fade" mode="out-in">
                <div  v-if="loading.details"  class="div-preloader-wrap">
                    <div class=" custom-center">
                        <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                    </div>
                </div>
            </transition>
            <div class="card-content collapse show">
                <div class="card-body">
                    <form class="form">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">First Name</label>
                                        <input id="first_name" v-model="customer.first_name" class="form-control round" placeholder="Enter first name" type="text" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">Last Name</label>
                                        <input id="last_name" v-model="customer.last_name"  class="form-control round" placeholder="Enter last name" type="text" required> 
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input id="email" v-model="customer.email"  class="form-control round" placeholder="Enter email" type="email" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <phone-input v-model="customer.phone"></phone-input>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions" style="text-align:center; padding: 0;">
                            <button @click.prevent="triggerDetailsUpdate()" class="btn btn-success">
                                    Update {{ customer.first_name }}'s Details
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import PhoneInput from './PhoneInput.vue';


export default {
    components: {
        'phone-input' : PhoneInput
    },
    props: ['customer', 'loading'],
    data(){
        return{
            
        }
    },
    methods: {
        triggerDetailsUpdate(){
            this.$emit("updateDetails", "")
        }
    }
}
</script>