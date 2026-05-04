import React, { useState, useRef, useEffect } from 'react';

const ChatBot = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState([
    { role: 'bot', text: "Hello! I am FlowCraft Agent. Tell me to run a workflow!" }
  ]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const messagesEndRef = useRef(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const handleSend = async () => {
    if (!input.trim() || loading) return;

    const userMsg = input;
    setInput('');
    setMessages(prev => [...prev, { role: 'user', text: userMsg }]);
    setLoading(true);

    try {
      const res = await fetch('/api/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: userMsg })
      });

      const data = await res.json();
      // نحول الـ ** إلى bold ونحول \n إلى أسطر جديدة عشان يبانوا حلوين بالشات
      const formattedText = data.reply
        .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
        .replace(/\n/g, "<br/>");

      setMessages(prev => [...prev, { role: 'bot', text: formattedText }]);
    } catch (err) {
      setMessages(prev => [...prev, { role: 'bot', text: "❌ Connection error." }]);
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      {/* زر الفتح والإغلاق */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="fixed bottom-6 right-6 w-16 h-16 bg-gradient-to-r from-cyan-500 to-blue-500 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl hover:scale-110 transition-transform z-50 border-4 border-white"
      >
        {isOpen ? '✕' : '💬'}
      </button>

      {/* نافذة الشات */}
      {isOpen && (
        <div className="fixed bottom-24 right-6 w-96 h-[500px] bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col z-50 overflow-hidden">
          
          {/* رأس الشات */}
          <div className="bg-gradient-to-r from-cyan-500 to-blue-500 text-white p-4 flex items-center gap-3">
            <div className="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-xl">🤖</div>
            <div>
              <h3 className="font-bold">FlowCraft Agent</h3>
              <p className="text-xs text-cyan-100">Online • Ready to execute</p>
            </div>
          </div>

          {/* الرسائل */}
          <div className="flex-1 p-4 overflow-y-auto bg-gray-50 space-y-3">
            {messages.map((msg, i) => (
              <div key={i} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                <div 
                  className={`max-w-[80%] p-3 rounded-2xl text-sm leading-relaxed ${
                    msg.role === 'user' 
                      ? 'bg-blue-500 text-white rounded-br-none' 
                      : 'bg-white border border-gray-200 text-gray-700 rounded-bl-none shadow-sm'
                  }`}
                  dangerouslySetInnerHTML={{ __html: msg.text }} // لدعم الـ Bold والأسطر الجديدة
                />
              </div>
            ))}
            {loading && (
              <div className="flex justify-start">
                <div className="bg-white border border-gray-200 p-3 rounded-2xl rounded-bl-none shadow-sm text-gray-400 text-sm">
                  Typing...
                </div>
              </div>
            )}
            <div ref={messagesEndRef} />
          </div>

          {/* خانة الكتابة */}
          <div className="p-3 border-t border-gray-100 bg-white flex gap-2">
            <input
              type="text"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && handleSend()}
              placeholder="e.g. Run My Workflow..."
              className="flex-1 px-4 py-2.5 bg-gray-50 rounded-xl border border-gray-200 focus:outline-none focus:border-cyan-400 text-sm"
            />
            <button
              onClick={handleSend}
              disabled={loading}
              className="bg-cyan-500 hover:bg-cyan-600 text-white px-4 rounded-xl font-bold transition-colors disabled:opacity-50"
            >
              Send
            </button>
          </div>
        </div>
      )}
    </>
  );
};

export default ChatBot;