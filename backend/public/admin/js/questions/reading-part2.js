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
                // Ensure at least 6 if less than 6 (protection for old data)
                while (this.sentences.length < 6) {
                    this.sentences.push({ id: this.sentences.length + 1, text: '' });
                }
            } else {
                this.sentences = Array.from({ length: 6 }, (_, i) => ({ id: i + 1, text: '' }));
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
            if (this.sentences.length === 0) return;
            
            // The first sentence is FIXED at the top.
            const fixedSentence = this.sentences[0];
            const otherSentences = [...this.sentences.slice(1)];
            
            // Shuffle algorithm (Fisher-Yates) for others
            for (let i = otherSentences.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [otherSentences[i], otherSentences[j]] = [otherSentences[j], otherSentences[i]];
            }
            
            this.shuffledSentences = [fixedSentence, ...otherSentences];
        },

        // initSortable and reorderPreview removed as per user request to disable drag in preview
    }));
});
