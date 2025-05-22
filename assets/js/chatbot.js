document.addEventListener('DOMContentLoaded', function() {
    const chatbotToggle = document.getElementById('chatbot-toggle');
    const chatbotContainer = document.createElement('div');
    chatbotContainer.id = 'chatbot-container';
    chatbotContainer.style.display = 'none';
    
    // Tạo giao diện chatbot
    chatbotContainer.innerHTML = `
        <div class="fixed bottom-20 right-4 w-80 bg-white rounded-lg shadow-xl overflow-hidden z-50">
            <div class="bg-black text-white px-4 py-3 flex justify-between items-center">
                <h3 class="font-semibold">Nike Assistant</h3>
                <button id="chatbot-close" class="text-white hover:text-gray-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="chatbot-messages" class="h-96 overflow-y-auto p-4 space-y-4"></div>
            <div class="border-t p-4">
                <form id="chatbot-form" class="flex space-x-2">
                    <input type="text" id="chatbot-input" 
                           class="flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-black"
                           placeholder="Nhập tin nhắn...">
                    <button type="submit" class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800">
                        Gửi
                    </button>
                </form>
            </div>
        </div>
    `;
    
    document.body.appendChild(chatbotContainer);
    
    const chatbotClose = document.getElementById('chatbot-close');
    const chatbotMessages = document.getElementById('chatbot-messages');
    const chatbotForm = document.getElementById('chatbot-form');
    const chatbotInput = document.getElementById('chatbot-input');
    
    // Hiển thị/ẩn chatbot
    chatbotToggle.addEventListener('click', function() {
        if (chatbotContainer.style.display === 'none') {
            chatbotContainer.style.display = 'block';
            // Thêm tin nhắn chào mừng nếu là lần đầu mở
            if (chatbotMessages.children.length === 0) {
                addMessage('bot', 'Xin chào! Tôi là Nike Assistant. Tôi có thể giúp gì cho bạn?');
            }
        } else {
            chatbotContainer.style.display = 'none';
        }
    });
    
    chatbotClose.addEventListener('click', function() {
        chatbotContainer.style.display = 'none';
    });
    
    // Xử lý gửi tin nhắn
    chatbotForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const message = chatbotInput.value.trim();
        if (!message) return;
        
        // Hiển thị tin nhắn của người dùng
        addMessage('user', message);
        chatbotInput.value = '';
        
        // Hiển thị trạng thái đang nhập
        const typingIndicator = addMessage('bot', 'Đang nhập...');
        
        try {
            // Gửi yêu cầu đến server
            const response = await fetch('/includes/chatbot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: message })
            });
            
            const data = await response.json();
            
            // Xóa trạng thái đang nhập
            typingIndicator.remove();
            
            if (data.success) {
                // Hiển thị phản hồi từ chatbot
                addMessage('bot', data.response);
            } else {
                // Hiển thị thông báo lỗi
                addMessage('bot', 'Xin lỗi, tôi đang gặp sự cố. Vui lòng thử lại sau.');
            }
        } catch (error) {
            // Xóa trạng thái đang nhập
            typingIndicator.remove();
            // Hiển thị thông báo lỗi
            addMessage('bot', 'Xin lỗi, đã có lỗi xảy ra. Vui lòng thử lại sau.');
        }
    });
    
    // Hàm thêm tin nhắn vào khung chat
    function addMessage(sender, content) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `flex ${sender === 'user' ? 'justify-end' : 'justify-start'}`;
        
        const messageBubble = document.createElement('div');
        messageBubble.className = `max-w-[75%] rounded-lg px-4 py-2 ${
            sender === 'user' 
                ? 'bg-black text-white' 
                : 'bg-gray-100 text-gray-800'
        }`;
        messageBubble.textContent = content;
        
        messageDiv.appendChild(messageBubble);
        chatbotMessages.appendChild(messageDiv);
        
        // Cuộn xuống tin nhắn mới nhất
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        
        return messageDiv;
    }
});
