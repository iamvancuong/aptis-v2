<div class="space-y-6">
    <p class="text-sm text-gray-500 mb-2">
        Thiết kế bài Writing Part 4 & 5 (gộp). Học sinh đọc email/thư, sau đó viết 2 email:
        <strong>Task 1</strong> gửi bạn (~50 từ, informal) và <strong>Task 2</strong> gửi quản lý (~120-150 từ, formal).
    </p>

    {{-- ===== SHARED CONTEXT: The email student reads ===== --}}
    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
        <h4 class="text-sm font-bold text-blue-800 mb-3">📧 Email / Thư mà học sinh đọc (Shared Context)</h4>

        <!-- Context / Situation -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Ngữ cảnh (Context)</label>
            <textarea name="metadata[context]"
                x-model="context"
                rows="2"
                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="e.g. You are a member of a book club. You received this email from the club."
                required></textarea>
        </div>

        <!-- Email Greeting -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Lời chào (Greeting)</label>
            <input type="text" name="metadata[email][greeting]"
                x-model="emailGreeting"
                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="e.g. Dear Member,">
        </div>

        <!-- Email Body -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nội dung email (Email Body)</label>
            <textarea name="metadata[email][body]"
                x-model="emailBody"
                rows="5"
                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="e.g. Our club is organizing a talk by a famous writer next year. Which writer that you think we should invite to speak, and what topic should the writer speak on?..."
                required></textarea>
        </div>

        <!-- Email Sign-off -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kí tên (Sign-off)</label>
            <input type="text" name="metadata[email][sign_off]"
                x-model="emailSignOff"
                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="e.g. The manager,">
        </div>
    </div>

    {{-- ===== TASK 1: Informal email to friend (~50 words) ===== --}}
    <div class="p-4 bg-green-50 rounded-lg border border-green-200">
        <h4 class="text-sm font-bold text-green-800 mb-3">✉️ Task 1 — Email gửi bạn (Informal, ~50 từ)</h4>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Yêu cầu (Task Instruction)</label>
            <textarea name="metadata[task1][instruction]"
                x-model="task1Instruction"
                rows="3"
                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                placeholder="e.g. Write an email to your friend, who is also a member of the club. Tell your friend what suggestions you will make and why. Write about 50 words. You have 10 minutes."
                required></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Min Words</label>
                <input type="number" name="metadata[task1][word_limit][min]"
                    x-model.number="task1WordLimitMin"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                    value="40" min="1">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max Words</label>
                <input type="number" name="metadata[task1][word_limit][max]"
                    x-model.number="task1WordLimitMax"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                    value="50" min="1">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">💡 Sample Answer — Task 1</label>
            <textarea name="metadata[task1][sample_answer]"
                x-model="task1SampleAnswer"
                rows="3"
                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                placeholder="Đáp án gợi ý cho Task 1..."></textarea>
        </div>
    </div>

    {{-- ===== TASK 2: Formal email to manager (~120-150 words) ===== --}}
    <div class="p-4 bg-orange-50 rounded-lg border border-orange-200">
        <h4 class="text-sm font-bold text-orange-800 mb-3">📝 Task 2 — Email gửi quản lý (Formal, ~120-150 từ)</h4>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Yêu cầu (Task Instruction)</label>
            <textarea name="metadata[task2][instruction]"
                x-model="task2Instruction"
                rows="3"
                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                placeholder="e.g. Now write an email to the manager. Explain your suggestions and reasons. Write 120-150 words. You have 10 minutes."
                required></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Min Words</label>
                <input type="number" name="metadata[task2][word_limit][min]"
                    x-model.number="task2WordLimitMin"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                    value="120" min="1">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max Words</label>
                <input type="number" name="metadata[task2][word_limit][max]"
                    x-model.number="task2WordLimitMax"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                    value="150" min="1">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">💡 Sample Answer — Task 2</label>
            <textarea name="metadata[task2][sample_answer]"
                x-model="task2SampleAnswer"
                rows="6"
                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                placeholder="Đáp án gợi ý cho Task 2 (formal email)..."></textarea>
        </div>
    </div>
</div>
