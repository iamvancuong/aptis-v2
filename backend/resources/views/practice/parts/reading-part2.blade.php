{{-- Reading Part 2: Paragraph Ordering (Two-Column Drag & Drop) --}}
<template x-if="currentQuestion.skill === 'reading' && currentQuestion.part === 2">
    <div class="space-y-4">
        <p class="text-sm text-gray-500 italic">Drag sentences from the right panel into the correct order on the left.</p>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- LEFT: Drop Zones (Order Slots) --}}
            <div class="space-y-3">
                <h4 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-2">Your Order</h4>
                
                {{-- Fixed First Sentence --}}
                <div class="p-3 bg-gray-100 rounded-lg border border-gray-200 text-gray-500 flex gap-3 cursor-not-allowed opacity-80">
                    <span class="font-bold text-gray-400 w-6 text-center">1</span>
                    <span class="text-sm" x-text="currentQuestion.metadata.sentences[0]"></span>
                </div>
                
                {{-- Droppable Slots --}}
                <template x-for="(slot, slotIdx) in part2Slots" :key="'slot-'+slotIdx">
                    <div class="p-3 rounded-lg border-2 border-dashed min-h-[56px] transition-all"
                         :class="[
                            hasAnswered(currentQuestion.id) 
                                ? (slot && slot.originalIndex === slotIdx + 1 ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50')
                                : (slot ? 'border-gray-300 bg-white' : 'border-gray-300 bg-gray-50'),
                            p2DragOverSlot === slotIdx && !hasAnswered(currentQuestion.id) ? 'border-blue-500 bg-blue-50 scale-[1.02]' : ''
                         ]"
                         @dragover.prevent="if(!hasAnswered(currentQuestion.id)) p2DragOverSlot = slotIdx"
                         @dragleave="p2DragOverSlot = null"
                         @drop.prevent="dropToSlot(slotIdx)"
                    >
                        {{-- Row 1: Number + Sentence + Remove Button --}}
                        <div class="flex items-center gap-3">
                            <span class="font-bold w-6 text-center flex-shrink-0" 
                                  :class="hasAnswered(currentQuestion.id) && slot
                                      ? (slot.originalIndex === slotIdx + 1 ? 'text-green-600' : 'text-red-600')
                                      : 'text-blue-600'"
                                  x-text="slotIdx + 2"></span>
                            
                            <template x-if="slot">
                                <div class="flex-1 flex items-center justify-between gap-2">
                                    <span class="text-sm text-gray-800" x-text="slot.text"></span>
                                    <button x-show="!hasAnswered(currentQuestion.id)" 
                                            @click="removeFromSlot(slotIdx)"
                                            class="text-gray-400 hover:text-red-500 flex-shrink-0 p-1">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </template>
                            
                            <template x-if="!slot">
                                <span class="text-sm text-gray-400 italic">Drop a sentence here...</span>
                            </template>
                        </div>

                        {{-- Row 2: Feedback (below sentence) --}}
                        <template x-if="hasAnswered(currentQuestion.id) && slot">
                            <div class="mt-2 ml-9 text-sm">
                                <div x-show="slot.originalIndex === slotIdx + 1" class="flex items-center text-green-600 font-bold">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Correct
                                </div>
                                <div x-show="slot.originalIndex !== slotIdx + 1" class="flex items-start text-red-600">
                                    <svg class="w-4 h-4 mr-1 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    <span><span class="font-bold">Incorrect.</span> Answer: <span class="font-bold text-green-600" x-text="currentQuestion.metadata.sentences[slotIdx + 1]"></span></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- RIGHT: Sentence Pool --}}
            <div class="space-y-3">
                <h4 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-2">Sentences (drag from here)</h4>
                
                <template x-for="(item, poolIdx) in part2Pool" :key="'pool-'+item.originalIndex">
                    <div class="p-3 bg-white rounded-lg border border-orange-200 flex items-center gap-3 transition-all hover:shadow-md"
                         :class="p2DraggingPoolIdx === poolIdx ? 'opacity-40' : 'cursor-move hover:border-orange-400'"
                         draggable="true"
                         @dragstart="p2PoolDragStart($event, poolIdx)"
                         @dragend="p2DraggingPoolIdx = null"
                    >
                        <span class="text-orange-400 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
                        </span>
                        <span class="text-sm text-gray-800" x-text="item.text"></span>
                    </div>
                </template>
                
                <div x-show="part2Pool.length === 0 && !hasAnswered(currentQuestion.id)" class="p-4 text-center text-gray-400 text-sm italic border border-dashed border-gray-300 rounded-lg">
                    All sentences placed! Click "Submit Order" to check.
                </div>
            </div>
        </div>
        
        <button x-show="!hasAnswered(currentQuestion.id)" 
                @click="submitPart2()" 
                :disabled="part2Pool.length > 0"
                class="mt-4 w-full py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
            Submit Order
        </button>

        {{-- Correct Order Feedback --}}
        <div x-show="hasAnswered(currentQuestion.id) && feedback[currentQuestion.id] && !feedback[currentQuestion.id].correct" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <h4 class="font-bold text-yellow-800 mb-2">✅ Correct Order:</h4>
            <ol class="list-decimal list-inside space-y-1 text-yellow-900 text-sm">
                <li x-text="currentQuestion.metadata.sentences[0]"></li>
                <template x-for="(sentence, sIdx) in currentQuestion.metadata.sentences.slice(1)" :key="sIdx">
                    <li x-text="sentence"></li>
                </template>
            </ol>
        </div>
    </div>
</template>
