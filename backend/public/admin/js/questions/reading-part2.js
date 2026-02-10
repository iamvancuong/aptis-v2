document.addEventListener('alpine:init', () => {
    Alpine.data('readingPart2', (initialData = null) => ({
        sentences: [],
        shuffledSentences: [], // For Preview (Student View)

        init() {
            if (initialData && initialData.sentences) {
                this.sentences = initialData.sentences.map((text, i) => ({
                    id: i + 1,
                    text: text
                }));
            } else {
                this.sentences = Array.from({ length: 5 }, (_, i) => ({ id: i + 1, text: '' }));
            }
            
            // Initial shuffle for preview
            this.shuffleSentences();
            
            // Watch for changes/additions if necessary, but shallow copy reference puts text updates in sync
            this.$watch('sentences', () => {
                 // Check if we need to resync structural changes (added/removed items)
                 // For simple text updates, references handle it.
                 // If lengths differ, re-shuffle.
                 if (this.sentences.length !== this.shuffledSentences.length) {
                     this.shuffleSentences();
                 }
            });
        },

        shuffleSentences() {
            // Create shallow copy to preserve object references (so text updates sync)
            this.shuffledSentences = [...this.sentences];
            
            // Shuffle algorithm (Fisher-Yates)
            for (let i = this.shuffledSentences.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [this.shuffledSentences[i], this.shuffledSentences[j]] = [this.shuffledSentences[j], this.shuffledSentences[i]];
            }
        },

        // initSortable and reorderPreview removed as per user request to disable drag in preview
    }));
});
