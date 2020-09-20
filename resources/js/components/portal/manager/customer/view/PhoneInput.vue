<template>
    <input id="phone" v-model="displayValue"  @blur="isInputActive = false" @focus="isInputActive = true" class="form-control round" placeholder="Enter phone e.g. 0204456789" type="text" required>
</template>

<script>
export default {
    props: ["value"],
    data: function() {
        return {
            isInputActive: false,
        }
    },
    computed: {
        displayValue: {
            get: function() {
                return (this.isInputActive) ? this.value : "0"+this.value.substr(3);
            },
            set: function(modifiedValue) {
                this.$emit('input', modifiedValue)
            }
        }
    }
}
</script>