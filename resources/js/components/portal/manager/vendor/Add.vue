<template>
    <div>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-5">
                            <h5 class="card-title">
                                Add Vendor
                            </h5>
                            <div class="card" style="">
                                <transition name="fade" mode="out-in">
                                    <div  v-if="loading"  class="div-preloader-wrap">
                                        <div class=" custom-center">
                                            <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                                        </div>
                                    </div>
                                </transition>
                                <div class="card-content collapse show">
                                    <div class="card-body">
                                        <div class="form-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="name">Name</label>
                                                        <input v-model="vendor.name" class="form-control round" placeholder="Enter Vendor name" type="text">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="email">Email</label>
                                                        <input v-model="vendor.email"  class="form-control round" placeholder="Enter email" type="email"> 
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="main_phone">Main Phone Number</label>
                                                        <input v-model="vendor.phone" class="form-control round" placeholder="Enter main phone e.g. 0204456789" type="text">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="alt_phone">Alternate Phone Number</label>
                                                        <input v-model="vendor.alt_phone"  class="form-control round" placeholder="Enter alternate phone e.g. 0204456789" type="text">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="mode_of_payment">Mode of Payment</label>
                                                        <fieldset class="form-group" >
                                                            <select v-model="vendor.mode_of_payment" class="form-control" style='border-radius:7px;'>
                                                                <option>MTN Mobile Money</option>
                                                                <option>Vodafone Cash</option>
                                                                <option>AirtelTigo Money</option>
                                                                <option>Bank Account</option>
                                                            </select>
                                                        </fieldset>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="payment_details">Payment Details</label>
                                                        <input v-model="vendor.payment_details" class="form-control round" placeholder="Enter payment details" type="text">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="address">Address</label>
                                                        <input v-model="vendor.address" class="form-control round" placeholder="Enter Pick-Up Address" type="text">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="header_image">Header Image</label>
                                                        <input type="file" class="form-control-file" @change="handleFileUpload()" ref="header" id="header">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-actions" style="text-align:center; padding: 0px;">
                                            <button @click="add()" class="btn btn-success">
                                                    Add Vendor {{ vendor.first_name }}
                                            </button>
                                        </div>
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
            vendor: {
                name: '',
                email: '',
                phone: '',
                alt_phone: '',
                address: '',
                mode_of_payment: '',
                payment_details: '',
                header: ''
            },
            loading: true,
            base_url: window.location.origin,
        }
    },
    mounted(){
        this.loading = false
    },
    methods: {
        async add(){
            this.loading = true;
            let formData = new FormData();
            formData.append('name', this.vendor.name);
            formData.append('email', this.vendor.email);
            formData.append('phone', this.vendor.phone);
            formData.append('alt_phone', this.vendor.alt_phone);
            formData.append('address', this.vendor.address);
            formData.append('mode_of_payment', this.vendor.mode_of_payment);
            formData.append('payment_details', this.vendor.payment_details);
            formData.append('header', this.vendor.header);

            var response = await axios.post('https://www.solushop.com.gh/portal/manager/vendors/add', formData, {
                headers: {
                'Content-Type': 'multipart/form-data'
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

                this.vendor.name = '';
                this.vendor.email = '';
                this.vendor.main_phone = '';
                this.vendor.alt_phone = '';
                this.vendor.address = '';
                this.vendor.mode_of_payment = '';
                this.vendor.payment_details = '';
                this.vendor.header = '';                
            }

            this.loading = false;
        },

        handleFileUpload(){
            this.vendor.header = this.$refs.header.files[0];
        }
    }
}
</script>