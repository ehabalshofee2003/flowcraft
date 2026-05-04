import React, { useState, useRef, useEffect } from 'react';

const AiChat = ({ onWorkflowGenerated }) => {
  const [messages, setMessages] = useState([
    { role: 'assistant', text: "مرحباً! أنا مساعدك الذكي. أخبرني بماذا تريد أن تؤتمت (مثال: أريد فحص الإيميل، إذا يحتوي على كلمة 'important' غير لونه للأحمر)." }
  ]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const chatEndRef = useRef(null);

  const scrollToBottom = () => {
    chatEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const sendMessage = async (e) => {
    e.preventDefault();
    if (!input.trim() || loading) return;

    const userMessage = input;
    setInput('');
    setMessages(prev => [...prev, { role: 'user', text: userMessage }]);
    setLoading(true);

    try {
      const res = await fetch('/api/ai/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: userMessage })
      });

      const data = await res.json();

      if (data.error) {
        setMessages(prev => [...prev, { role: 'assistant', text: `❌ خطأ: ${data.error}` }]);
      } else {
        // 1. عرض رسالة التفسير
        setMessages(prev => [...prev, { 
          role: 'assistant', 
          text: `🧠 ${data.explanation}\n\n✅ تم إنشاء الرسم البياني! اضغط على الزر أدناه لتطبيقه على الشاشة.`,
          workflow: data.workflow // نخزن الـ JSON جوا الرسالة عشان نستخدمه لاحقاً
        }]);
      }
    } catch (err) {
      setMessages(prev => [...prev, { role: 'assistant', text: '❌ فشل الاتصال بالسيرفر.' }]);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex flex-col h-full bg-[#0f172a] text-white">
      {/* Header */}
      <div className="p-4 border-b border-slate-700/50 flex items-center gap-3 bg-[#1e293b]">
        <div className="w-8 h-8 bg-gradient-to-br from-blue-500 to-violet-600 rounded-full flex items-center justify-center text-sm font-bold">AI</div>
        <div>
          <h3 className="font-bold text-sm text-slate-200">Workflow Generator</h3>
          <p className="text-[10px] text-slate-400">Powered by GPT-3.5</p>
        </div>
      </div>

      {/* Messages Area */}
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {messages.map((msg, index) => (
          <div key={index} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
            <div className={`max-w-[85%] p-3 rounded-2xl text-sm leading-relaxed ${
              msg.role === 'user' 
                ? 'bg-blue-600 text-white rounded-br-sm' 
                : 'bg-slate-800 text-slate-200 rounded-bl-sm border border-slate-700/50'
            }`}>
              <p className="whitespace-pre-wrap">{msg.text}</p>
              
              {/* زر التطبيق السحري يظهر فقط إذا كانت الرسالة فيها workflow */}
              {msg.workflow && (
                <button 
                  onClick={() => onWorkflowGenerated(msg.workflow)}
                  className="mt-3 w-full bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2"
                >
                  <span>✨</span> تطبيق على الكانفاس (Apply to Canvas)
                </button>
              )}
            </div>
          </div>
        ))}
        
        {loading && (
          <div className="flex justify-start">
            <div className="bg-slate-800 p-3 rounded-2xl rounded-bl-sm border border-slate-700/50">
              <div className="flex gap-1">
                <div className="w-2 h-2 bg-slate-500 rounded-full animate-bounce" style={{animationDelay: '0ms'}}></div>
                <div className="w-2 h-2 bg-slate-500 rounded-full animate-bounce" style={{animationDelay: '150ms'}}></div>
                <div className="w-2 h-2 bg-slate-500 rounded-full animate-bounce" style={{animationDelay: '300ms'}}></div>
              </div>
            </div>
          </div>
        )}
        <div ref={chatEndRef} />
      </div>

      {/* Input Area */}
      <div className="p-4 border-t border-slate-700/50 bg-[#1e293b]">
        <form onSubmit={sendMessage} className="flex gap-2">
          <input
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            placeholder="مثال: اكتب مرحباً وغير لونها للأزرق..."
            className="flex-1 bg-slate-800 text-white border border-slate-600 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 placeholder-slate-500"
            disabled={loading}
          />
          <button 
            type="submit" 
            disabled={loading}
            className="bg-blue-600 hover:bg-blue-700 disabled:bg-slate-700 text-white px-5 py-3 rounded-xl font-bold transition-colors"
          >
            ⬆
          </button>
        </form>
      </div>
    </div>
  );
};

export default AiChat;