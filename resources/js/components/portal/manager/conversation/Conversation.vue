<template>
    <div>
        <transition name="fade" mode="out-in">
            <div  v-if="loading.main"  class="preloader-wrap">
                <div class=" custom-center">
                    <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                </div>
            </div>
        </transition>
        <div v-if="conversation" class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="card-title">Conversation between {{ conversation.customer.first_name+" "+conversation.customer.last_name }} and {{ conversation.vendor.name }} </h5>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-7">
                            <div class="card" style="margin-bottom:5px;">
                                <div class="card-content collapse show">
                                    <div class="card-body" style="height: 520px; overflow: hidden;">
                                        <div class="child" id='Child'>
                                            <transition name="fade" mode="out-in">
                                                <div  v-if="loading.chat"  class="div-preloader-wrap">
                                                    <div class=" custom-center">
                                                        <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                                                    </div>
                                                </div>
                                            </transition>
                                            <h4 v-if="count.old < 1" style='text-align:center; margin-top:250px; font-weight: 300'>
                                                No messages yet
                                            </h4>
                                            <div v-else v-for="message in conversation.messages" :key="message.id">
                                                <div v-if="message.message_sender == conversation.customer.id" class='chat-row' style='text-align: left; '>
                                                    <div class='chat-item' style='color: #001337; background-color: #edeef0; border-radius:10px; padding:7px; max-width:400px; display: inline-block; text-align: left; font-size: 12px'>
                                                        {{ message.message_content }}
                                                    </div>
                                                    <br>
                                                    <span style='font-size:10px; font-weight:300'>
                                                        {{ conversation.customer.first_name + " - " + message.message_timestamp }}
                                                    </span>
                                                </div>
                                                <div v-if="message.message_sender == conversation.vendor.id" class='chat-row' style='text-align: left; '>
                                                    <div class='chat-item' style='color: #001337; background-color: #edeef0; border-radius:10px; padding:7px; max-width:400px; display: inline-block; text-align: left; font-size: 12px'>
                                                        {{ message.message_content }}
                                                    </div>
                                                    <br>
                                                    <span style='font-size:10px; font-weight:300'>
                                                        {{ conversation.vendor.name + " - " + message.message_timestamp  }}
                                                    </span>
                                                </div>
                                                <div v-if="message.message_sender == 'MGMT'" class='chat-row' style='text-align: right; word-wrap: break-word'>
                                                    <div class='chat-item' style='color: #fff; background-color: #001337; border-radius:10px; padding:7px; max-width:400px; display: inline-block; text-align: left; font-size:12px;'>
                                                        {{ message.message_content }}
                                                    </div>
                                                    <br>
                                                    <span style='font-size:11px; font-weight:300'>
                                                        {{ "Solushop Management - " + message.message_timestamp }}
                                                    </span>
                                                </div>
                                                <br>
                                            </div> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom: 10px;">
                                <input v-model="message"  class="form-control round" value="" type="text">
                            </div>
                            <div class="form-actions" style="text-align:center; padding: 0px;">
                                <button @click="send()" class="btn btn-success">
                                        Send Message
                                </button>
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
    props: ['id'],
    data(){
        return {
            conversation: null,
            loading: {
                main: true,
                chat: false
            },
            count: {
                old: 0,
                new: 0
            },
            message: "",
            chat: null,
            base_url: window.location.origin,
        }
    },

    async mounted(){
        await this.updateRecords();
        document.title = "Conversation between " + this.conversation.customer.first_name + " " + this.conversation.customer.last_name + " and " + this.conversation.vendor.name;
        var chat = document.getElementById("Child");
        chat.scrollTop = chat.scrollHeight;
        this.loading.main = false;
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
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/conversation/count',
                                data: {
                                    id: this.id
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

            this.setCount(response.data.count);
            if (this.count.old != this.count.new && this.loading.main != true) {
                this.loading.chat = true;

                //update old counts
                this.count.old = this.count.new;

                await this.updateRecords();
                this.loading.chat = false;
            }
        },

        async updateRecords(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/conversation/records',
                                data: {
                                    id: this.id
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
            this.conversation = response.data.records;
            this.setCount(response.data.count);
        },

        async send(){
            this.loading.main = true;
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/conversation/send',
                                data: {
                                    id: this.id,
                                    message: this.message
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
                this.loading.main = false;
                //show error message(s)
                for (let i = 0; i < response.data.message.length ; i++) {
                    this.$toast.error(response.data.message[i], "", {
                        timeout: 5000,
                    });
                }
            } else {
                //show success message
                
                this.message = "";
                this.conversation.messages = response.data.messages;
                this.count.old = response.data.count;
                this.loading.main = false;
                var chat = document.getElementById("Child");
                chat.scrollTop = chat.scrollHeight;
                this.$toast.success(response.data.message, "", {
                    timeout: 5000,
                });


            }
            
            
        }
    },
    
}
</script>