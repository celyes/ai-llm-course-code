<script setup>
import {ref, toRaw} from "vue";
import {marked} from "marked";

let chatMessageResponse = ref('')
let generating = ref(false)

let prompt = ref([
    {role: 'user', content: ''}
])

const scrollToBottom = () => {
    window.scrollTo(0, document.body.scrollHeight);
}

const updateLastMessageBody = (chunk) => {
    chatMessageResponse.value += `${chunk}`
}
const interpretLineBreaks = (text) => {
    const map = {
        '\n': '<br>',
        '\r': '',
        '<': '&lt;',
        '>': '&gt;',
        '&': '&amp;'
    };

    return text.replace(/[<>&\n\r]/g, m => map[m])
}
const generate = async () => {
    generating.value = true
    chatMessageResponse.value = ''
    const data = JSON.stringify({'message': toRaw(prompt.value)})
    console.log(data)
    const stream = await fetch('http://localhost:8000/api/chat/stream', {
        method: "POST",
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: data
    });
    if (stream.ok) {
        const reader = stream.body.getReader()
        const decoder = new TextDecoder()
        const readStream = () => {
            return reader.read().then(({done, value}) => {
                if (done) {
                    return
                }
                const chunk = decoder.decode(value)
                updateLastMessageBody(chunk !== '' ? chunk : 'empty message')
                scrollToBottom()
                return readStream()
            });
        }
        await readStream()
    }
    prompt.value = [
        {role: 'user', content: ''}
    ]
    generating.value = false
}
</script>

<template>
    <div class="p-10">
        <div class="grid grid-cols-12 gap-5">
            <div
                :class="{'cursor-not-allowed opacity-50': generating}"
                class="transition-all duration-300 col-span-12 rounded-xl p-3 border-2 border-gray-400 border-dashed focus-within:outline-none focus-within:border-green-500">
                <div class="flex justify-center items-center align-middle">
                    <input
                        :class="{'cursor-not-allowed': generating}"
                        class="w-full rounded-lg border-0 px-3 focus:outline-none focus:ring-0 "
                        placeholder="Say something..."
                        v-model="prompt[0].content"
                        :disabled="generating"
                    />
                    <button
                        :class="{'cursor-not-allowed': generating}"
                        class="text-xs text-gray-950 font-bold bg-green-400 rounded-xl py-2 px-4
                hover:bg-lime-400 transition-colors ease-in-out"
                        @click="generate"
                        :disabled="generating">
                        Generate
                    </button>
                </div>

            </div>
            <div class="col-span-12 min-h-60 h-auto rounded-xl p-3 border-2 border-gray-400 border-dashed">
                <h1 class="text-xl font-extrabold">ChatGPT response</h1>
                <p class="my-4" v-html="interpretLineBreaks(chatMessageResponse)"></p>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
