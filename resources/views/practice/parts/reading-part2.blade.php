{{-- Reading Part 2: Paragraph Ordering (Two-Column Drag & Drop) --}}
<template x-if="currentQuestion.skill === 'reading' && currentQuestion.part === 2">
    <div class="space-y-4">
        <p class="text-sm text-gray-500 italic">Drag sentences from the right panel into the correct order on the left.</p>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- LEFT: Drop Zones (Order Slots) --}}
            <div class="space-y-3">
                <h4 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-2">Your Order</h4>
                
                {{-- Fixed First Sentence --}}
                <template x-if="currentQuestion.metadata.sentences[0]">
                    <div class="p-3 bg-gray-100 rounded-lg border border-gray-200 text-gray-500 flex gap-3 cursor-not-allowed opacity-80">
                        <span class="font-bold text-gray-400 w-6 text-center">0</span>
                        <span class="text-sm" x-html="currentQuestion.metadata.sentences[0]"></span>
                    </div>
                </template>
                
                {{-- Droppable Slots --}}
                <template x-for="(slot, slotIdx) in part2Slots" :key="'slot-'+slotIdx">
                    <div class="p-3 rounded-lg border-2 border-dashed min-h-[56px] transition-all cursor-pointer touch-action-pan-y"
                         :class="[
                            hasAnswered(currentQuestion.id) 
                                ? (slot && slot.originalIndex === slotIdx + 1 ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50')
                                : (slot ? 'border-gray-300 bg-white' : 'border-gray-300 bg-gray-50'),
                            p2DragOverSlot === slotIdx && !hasAnswered(currentQuestion.id) ? 'border-blue-500 bg-blue-50 scale-[1.02]' : '',
                            !hasAnswered(currentQuestion.id) && p2SelectedPoolIdx !== null && !slot ? 'border-blue-300 bg-blue-50/30' : ''
                         ]"
                         @dragover.prevent="if(!hasAnswered(currentQuestion.id)) p2DragOverSlot = slotIdx"
                         @dragleave="p2DragOverSlot = null"
                         @drop.prevent="dropToSlot(slotIdx)"
                         @click="selectP2Slot(slotIdx)"
                    >
                        {{-- Row 1: Number + Sentence + Remove Button --}}
                        <div class="flex items-center gap-3">
                            <span class="font-bold w-6 text-center flex-shrink-0" 
                                  :class="hasAnswered(currentQuestion.id) && slot
                                      ? (slot.originalIndex === slotIdx + 1 ? 'text-green-600' : 'text-red-600')
                                      : 'text-blue-600'"
                                  x-text="slotIdx + 1"></span>
                            
                            <template x-if="slot">
                                <div class="flex-1 flex items-center justify-between gap-2">
                                    <span class="text-sm text-gray-800" x-html="slot.text"></span>
                                    <button x-show="!hasAnswered(currentQuestion.id)" 
                                            @click.stop="removeFromSlot(slotIdx)"
                                            class="text-gray-400 hover:text-red-500 flex-shrink-0 p-1">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </template>
                            
                            <template x-if="!slot">
                                <div class="flex-1">
                                    <span class="text-sm text-gray-400 italic md:hidden">Tap to place selected sentence...</span>
                                    <span class="text-sm text-gray-400 italic hidden md:inline">Drop a sentence here...</span>
                                </div>
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
                                    <span><span class="font-bold">Incorrect.</span> Answer: <span class="font-bold text-green-600" x-html="currentQuestion.metadata.sentences[slotIdx + 1]"></span></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- RIGHT: Sentence Pool --}}
            <div class="space-y-3">
                <h4 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-2">Sentences (tap/drag)</h4>
                
                <template x-for="(item, poolIdx) in part2Pool" :key="'pool-'+item.originalIndex">
                    <div class="p-3 bg-white rounded-lg border flex items-center gap-3 transition-all hover:shadow-md cursor-pointer touch-action-pan-y"
                         :class="[
                            p2DraggingPoolIdx === poolIdx ? 'opacity-40' : 'hover:border-orange-400',
                            p2SelectedPoolIdx === poolIdx ? 'border-blue-500 ring-2 ring-blue-100 shadow-md' : 'border-orange-200'
                         ]"
                         draggable="true"
                         @dragstart="p2PoolDragStart($event, poolIdx)"
                         @dragend="p2DraggingPoolIdx = null"
                         @click="selectP2Pool(poolIdx)"
                    >
                        <span class="flex-shrink-0" :class="p2SelectedPoolIdx === poolIdx ? 'text-blue-500' : 'text-orange-400'">
                            {{-- Check icon when selected --}}
                            <svg x-show="p2SelectedPoolIdx === poolIdx" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{-- Drag icon when not selected --}}
                            <svg x-show="p2SelectedPoolIdx !== poolIdx" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                            </svg>
                        </span>
                        <span class="text-sm text-gray-800" x-html="item.text"></span>
                    </div>
                </template>
                
                <div x-show="part2Pool.length === 0 && !hasAnswered(currentQuestion.id) && !isFullTest" class="p-4 text-center text-gray-400 text-sm italic border border-dashed border-gray-300 rounded-lg">
                    All sentences placed! Click "Kiểm tra" below to check your answer.
                </div>
            </div>
        </div>

        {{-- Correct Order Feedback --}}
        <div x-show="hasAnswered(currentQuestion.id) && feedback[currentQuestion.id] && !feedback[currentQuestion.id].correct" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <h4 class="font-bold text-yellow-800 mb-2">✅ Correct Order:</h4>
            <ol class="list-decimal list-inside space-y-1 text-yellow-900 text-sm">
                <template x-if="currentQuestion.metadata.sentences[0]">
                    <li x-text="currentQuestion.metadata.sentences[0]"></li>
                </template>
                <template x-for="(sentence, sIdx) in currentQuestion.metadata.sentences.slice(1)" :key="sIdx">
                    <li x-html="sentence"></li>
                </template>
            </ol>
        </div>
    </div>
</template>
