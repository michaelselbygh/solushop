<template>
    <div>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-5">
                            <h5 class="card-title">
                                Add Sales Associate
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
                                                        <label for="first_name">First Name</label>
                                                        <input v-model="associate.first_name" class="form-control round" placeholder="Enter first name" type="text" >
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="last_name">Last Name</label>
                                                        <input v-model="associate.last_name"  class="form-control round" placeholder="Enter last name" type="text" > 
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="email">Email</label>
                                                        <input id="email" v-model="associate.email"  class="form-control round" placeholder="Enter email" type="email" >
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="phone">Phone Number</label>
                                                        <input v-model="associate.phone"  class="form-control round" placeholder="Enter phone e.g. 0204456789" type="text" >
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="mode_of_payment">Mode of Payment</label>
                                                        <fieldset class="form-group" >
                                                            <select class="form-control" v-model='associate.mode_of_payment' style='border-radius:7px;' >
                                                                <option>MTN Mobile Money</option>
                                                                <option>Vodafone Cash</option>
                                                                <option>Bank Account</option>
                                                            </select>
                                                        </fieldset>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="payment_details">Payment Details</label>
                                                        <input v-model="associate.payment_details" class="form-control round" placeholder="Enter payment details" type="text" >
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="id_type">Type Of Identification</label>
                                                        <select class="form-control" v-model='associate.id_type' style='border-radius:7px;' >
                                                            <option value='Voters ID'>Voters ID</option>
                                                            <option value='Drivers License'>Drivers License</option>
                                                            <option value='Passport'>Passport</option>
                                                            <option value='Ghana Card'>Ghana Card</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="id_file">Select Identification File</label>
                                                        <input type="file" class="form-control-file" id="id_file" ref="id_file" @change="handleFileUpload()" >
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="residential_address">Residential Address</label>
                                                <input v-model="associate.address" class="form-control round" placeholder="Enter Residential Address" type="text" >
                                            </div>
                                        </div>
                                        <div class="form-actions" style="text-align:center; padding: 0px;">
                                            <button @click="add()" class="btn btn-success">
                                                    Add Associate {{ associate.first_name }}
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
            associate: {
                first_name: '',
                last_name: '',
                email: '',
                phone: '',
                address: '',
                mode_of_payment: '',
                payment_details: '',
                id_type: '',
                id_file: ''
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
            formData.append('first_name', this.associate.first_name);
            formData.append('last_name', this.associate.last_name);
            formData.append('email', this.associate.email);
            formData.append('phone', this.associate.phone);
            formData.append('address', this.associate.address);
            formData.append('mode_of_payment', this.associate.mode_of_payment);
            formData.append('payment_details', this.associate.payment_details);
            formData.append('id_type', this.associate.id_type);
            formData.append('id_file', this.associate.id_file);

            var response = await axios.post('https://www.solushop.com.gh/portal/manager/sales-associates/add', formData, {
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

                this.associate.first_name= '';
                this.associate.last_name= '';
                this.associate.email= '';
                this.associate.phone= '';
                this.associate.address= '';
                this.associate.mode_of_payment= '';
                this.associate.payment_details= '';
                this.associate.id_type= '';
                this.associate.id_file= '';
                
            }

            this.loading = false;
        },

        handleFileUpload(){
            this.associate.id_file = this.$refs.id_file.files[0];
        }
    }
}
</script>