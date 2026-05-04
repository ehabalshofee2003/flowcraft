import React, { useCallback, useState, useEffect, useMemo } from 'react';
import ReactFlow, {
  addEdge,
  Background,
  Controls,
  applyNodeChanges,
  applyEdgeChanges,
} from 'reactflow';
import 'reactflow/dist/style.css';

import DynamicNode from './nodes/DynamicNode';
import AiChat from './components/AiChat'; // فقط هذا الملف نستخدمه

function App() {
  // --- States ---
  const [nodes, setNodes] = useState([]);
  const [edges, setEdges] = useState([]);
  const [result, setResult] = useState('');
  const [errors, setErrors] = useState([]);
  const [reactFlowInstance, setReactFlowInstance] = useState(null);
  const [draggedType, setDraggedType] = useState(null);
  const [availableNodes, setAvailableNodes] = useState({});
  const [chatOpen, setChatOpen] = useState(false); // <-- State فتح وإغلاق الشات

  // --- Fetch Nodes from Backend ---
  useEffect(() => {
    fetch('/api/node-types')
      .then(res => res.json())
      .then(data => setAvailableNodes(data.nodes || {}))
      .catch(err => console.error('Failed to load node types', err));
  }, []);

  // --- Bind all types to DynamicNode ---
  const nodeTypes = useMemo(() => {
    const types = {};
    for (const category in availableNodes) {
      for (const config of Object.values(availableNodes[category])) {
        types[config.type] = DynamicNode;
      }
    }
    return types;
  }, [availableNodes]);

  // --- React Flow Handlers ---
  const onNodesChange = useCallback((changes) => setNodes((nds) => applyNodeChanges(changes, nds)), []);
  const onEdgesChange = useCallback((changes) => setEdges((eds) => applyEdgeChanges(changes, eds)), []);
  const onConnect = useCallback((params) => setEdges((eds) => addEdge(params, eds)), []);

  // --- Smart Input Handler ---
  const createOnChangeHandler = useCallback((nodeId) => (fieldName, value) => {
    setNodes((nds) =>
      nds.map((node) =>
        node.id === nodeId ? { ...node, data: { ...node.data, [fieldName]: value } } : node
      )
    );
  }, []);

  // --- Drag & Drop Logic ---
  const onDragStart = (event, nodeType) => {
    setDraggedType(nodeType); 
    event.dataTransfer.setData('application/reactflow', nodeType);
    event.dataTransfer.effectAllowed = 'move';
  };

  const onDragOver = useCallback((event) => { event.preventDefault(); }, []);

  const onDrop = useCallback(
    (event) => {
      event.preventDefault();
      const type = draggedType; 
      if (!type || !reactFlowInstance) return;

      let nodeConfig = null;
      for (const category in availableNodes) {
        for (const config of Object.values(availableNodes[category])) {
          if (config.type === type) { nodeConfig = config; break; }
        }
        if (nodeConfig) break;
      }
      if (!nodeConfig) return;

      const bounds = event.currentTarget.getBoundingClientRect();
      const position = reactFlowInstance.project({
        x: event.clientX - bounds.left,
        y: event.clientY - bounds.top,
      });

      const id = `node_${Date.now()}`;
      const initialData = { _nodeConfig: nodeConfig, onChange: createOnChangeHandler(id) };
      
      (nodeConfig.schema?.fields || []).forEach(field => {
        initialData[field.name] = field.default ?? '';
      });

      setNodes((nds) => [...nds, { id, type, position, data: initialData }]);
    },
    [reactFlowInstance, availableNodes, createOnChangeHandler, draggedType]
  );

  // --- Data Cleaner ---
  const getCleanNodes = () => nodes.map(n => ({
    id: n.id, type: n.type, position: n.position,
    data: Object.fromEntries(Object.entries(n.data).filter(([key]) => !['_nodeConfig', 'onChange'].includes(key)))
  }));

  // --- Actions ---
  const runFlow = async () => {
    setErrors([]);
    setResult('<span style="color: #60a5fa; font-style: italic;">⏳ Executing workflow...</span>');
    
    try {
      const res = await fetch('/api/workflow/run', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json' // <-- أجبر الباك ند يرجع JSON حتى لو في خطأ
        },
        body: JSON.stringify({ nodes: getCleanNodes(), edges }),
      });
      
      // 1. نقرأ النص الخام (ممكن يكون JSON أو ممكن يكون HTML خطأ)
      const rawText = await res.text();
      
      let data;
      try {
        // 2. نحاول نحوله لـ JSON
        data = JSON.parse(rawText);
      } catch (e) {
        // 3. إذا فشل التحويل، معناته الباك ند رجع صفحة خطأ (HTML)
        setErrors(['خطأ داخلي بالسيرفر (PHP Error). شوف رسالة الخطأ بالأسفل:']);
        setResult('<span style="color: red; white-space: pre-wrap; font-size: 11px;">' + rawText.substring(0, 500) + '</span>');
        return;
      }

      // 4. إذا كان JSON صحيح، نتعامل معه طبيعي
      if (data.success) {
        setResult(data.output || '<span style="color: #34d399;">✔ Workflow executed successfully (No text output)</span>');
      } else {
        setErrors(data.errors || ['Execution failed']);
        setResult('');
      }
    } catch (err) {
      // 5. إذا فشل الاتصال تماماً (مثلاً السيرفر مطفي)
      setErrors(['فشل الاتصال بالسيرفر: ' + err.message]);
      setResult('');
    }
  };

  const saveWorkflow = async () => {
    try {
      const res = await fetch('/api/workflow/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nodes: getCleanNodes(), edges }),
      });
      const data = await res.json();
      alert('Saved successfully! ID: ' + data.workflow_id);
    } catch (err) { alert('Error saving workflow'); }
  };

  const loadWorkflow = async () => {
    const id = prompt('Enter workflow ID:');
    if (!id) return;
    try {
      const res = await fetch(`/api/workflow/${id}`);
      const data = await res.json();

      const loadedNodes = data.nodes.map((n) => {
        let nodeConfig = null;
        for (const category in availableNodes) {
          for (const config of Object.values(availableNodes[category])) {
            if (config.type === n.type) { nodeConfig = config; break; }
          }
          if (nodeConfig) break;
        }
        const nodeId = n.node_id || n.id;
        return {
          id: nodeId, type: n.type,
          position: n.position || { x: Math.random() * 400, y: Math.random() * 400 },
          data: { ...n.data, _nodeConfig: nodeConfig, onChange: createOnChangeHandler(nodeId) }
        };
      });

      const loadedEdges = data.edges.map((e) => ({
        id: e.id || `${e.source}-${e.target}`,
        source: e.source, target: e.target,
        sourceHandle: e.source_handle || null,
        targetHandle: e.target_handle || null,
      }));

      setNodes(loadedNodes);
      setEdges(loadedEdges);
    } catch (err) { alert('Error loading workflow'); }
  };

  // --- دالة استقبال الـ AI من الشات ---
  const handleAiWorkflow = useCallback((workflowData) => {
    if (!workflowData?.nodes) return;

    const newNodes = workflowData.nodes.map(node => {
      let nodeConfig = null;
      for (const category in availableNodes) {
        for (const config of Object.values(availableNodes[category])) {
          if (config.type === node.type) { nodeConfig = config; break; }
        }
        if (nodeConfig) break;
      }

      return {
        id: node.id,
        type: node.type,
        position: node.position || { x: Math.random() * 500, y: Math.random() * 500 },
        data: { 
          ...node.data, 
          _nodeConfig: nodeConfig, 
          onChange: createOnChangeHandler(node.id) 
        },
      };
    });

    const newEdges = workflowData.edges.map(edge => ({
      id: `e-${edge.source}-${edge.target}`,
      source: edge.source,
      target: edge.target,
      sourceHandle: edge.sourceHandle || null,
      targetHandle: edge.targetHandle || null,
    }));

    setNodes(nds => [...nds, ...newNodes]);
    setEdges(eds => [...eds, ...newEdges]);
    setChatOpen(false); // أغلق الشات بعد التطبيق
  }, [availableNodes, createOnChangeHandler]);

  const getNodeIcon = (type) => {
    switch (type) {
      case 'condition': return '🔀';
      case 'http_request': return '🌐';
      case 'delay': return '⏱️';
      case 'transform': return '🔄';
      case 'log': return '📝';
      case 'color': return '🎨';
      default: return '📦';
    }
  };

  return (
    <div className="h-screen flex flex-col bg-white font-sans overflow-hidden">

      {/* 🔝 Top Bar */}
      <div className="h-16 bg-gradient-to-r from-cyan-500 to-blue-500 text-white flex items-center justify-between px-6 shadow-lg shadow-cyan-500/20 z-10">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center text-white text-xl border border-white/30">
            ⚡
          </div>
          <h1 className="font-extrabold text-lg tracking-tight">FlowCraft</h1>
        </div>
        
        <div className="flex items-center gap-3">
          <button onClick={runFlow} className="bg-white text-cyan-600 px-6 py-2.5 rounded-xl font-bold shadow-md hover:shadow-lg hover:bg-cyan-50 transition-all duration-200 hover:scale-[1.03] active:scale-[0.98] flex items-center gap-2">
            <span>▶</span> Run
          </button>
          <button onClick={saveWorkflow} className="bg-emerald-400 text-white px-6 py-2.5 rounded-xl font-bold shadow-md hover:shadow-lg hover:bg-emerald-300 transition-all duration-200 hover:scale-[1.03] active:scale-[0.98] flex items-center gap-2">
            <span>💾</span> Save
          </button>
          <button onClick={loadWorkflow} className="bg-violet-400 text-white px-6 py-2.5 rounded-xl font-bold shadow-md hover:shadow-lg hover:bg-violet-300 transition-all duration-200 hover:scale-[1.03] active:scale-[0.98] flex items-center gap-2">
            <span>📥</span> Load
          </button>
          
          {/* زر فتح الشات الذكي */}
          <button onClick={() => setChatOpen(!chatOpen)} className="bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-500 hover:to-orange-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-md hover:shadow-lg transition-all duration-200 hover:scale-[1.03] active:scale-[0.98] flex items-center gap-2">
            <span>🤖</span> AI Chat
          </button>
        </div>
      </div>

      <div className="flex flex-1 overflow-hidden">

        {/* 📦 Sidebar */}
        <div className="w-72 bg-gradient-to-b from-blue-50 to-white border-r border-blue-100 p-5 overflow-y-auto shadow-sm">
          <div className="flex items-center gap-2 mb-6 pb-4 border-b border-blue-200/50">
            <span className="bg-blue-100 text-blue-600 p-1.5 rounded-lg text-sm">🧩</span>
            <h2 className="font-bold text-blue-800 text-sm">Node Library</h2>
          </div>
          
          {Object.entries(availableNodes).map(([category, nodes]) => (
            <div key={category} className="mb-6">
              <h3 className="text-[11px] font-bold text-blue-400 mb-3 uppercase tracking-widest">{category}</h3>
              <div className="space-y-2">
                {Object.values(nodes).map((config) => (
                  <div
                    key={config.type}
                    draggable
                    onDragStart={(e) => onDragStart(e, config.type)}
                    className="group p-3 bg-white border border-blue-100 rounded-xl cursor-grab hover:bg-cyan-50 hover:border-cyan-300 hover:shadow-lg hover:shadow-cyan-100/50 hover:-translate-y-0.5 transition-all duration-200 text-sm font-medium text-gray-600 flex items-center gap-3 active:cursor-grabbing active:shadow-none"
                  >
                    <div className="w-9 h-9 bg-blue-50 group-hover:bg-cyan-100 rounded-lg flex items-center justify-center text-base transition-colors duration-200">
                      {getNodeIcon(config.type)}
                    </div>
                    <span className="group-hover:text-cyan-700 transition-colors duration-200 font-semibold">{config.label}</span>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
        
        {/* 🎨 Canvas - مهم جداً: relative عشان الشات يطلع فوقه بالـ absolute */}
        <div className="flex-1 bg-gradient-to-br from-blue-50 via-cyan-50 to-emerald-50 relative">
          <ReactFlow
            nodes={nodes}
            edges={edges}
            nodeTypes={nodeTypes}
            onNodesChange={onNodesChange}
            onEdgesChange={onEdgesChange}
            onConnect={onConnect}
            onDrop={onDrop}
            onDragOver={onDragOver}
            onInit={setReactFlowInstance}
            fitView
          >
            <Background variant="dots" color="#93c5fd" gap={20} size={1.5} />
            <Controls className="!bg-white/80 !border-cyan-200 !shadow-lg !rounded-xl overflow-hidden backdrop-blur-sm [&>button]:!border-b [&>button]:!border-cyan-100 [&>button:hover]:!bg-cyan-50 [&>button]:!text-cyan-600" />
          </ReactFlow>

          {/* 🤖 الشاشة المنبثقة للشات الذكي (تطلع فوق الكانفاس) */}
          {chatOpen && (
            <div className="absolute bottom-6 right-6 w-[400px] h-[550px] rounded-2xl overflow-hidden shadow-2xl border border-slate-600 z-50 animate-[fadeIn_0.3s_ease-in-out]">
              <AiChat onWorkflowGenerated={handleAiWorkflow} />
            </div>
          )}
        </div>

        {/* 📤 Output Panel */}
        <div className="w-96 bg-[#0f172a] border-l border-slate-800 flex flex-col text-left">
          
          <div className="h-11 bg-[#1e293b] flex items-center px-4 border-b border-slate-700/50 shrink-0">
            <div className="flex gap-2 mr-4">
              <div className="w-3 h-3 rounded-full bg-red-500 hover:bg-red-400 transition-colors cursor-pointer"></div>
              <div className="w-3 h-3 rounded-full bg-yellow-500 hover:bg-yellow-400 transition-colors cursor-pointer"></div>
              <div className="w-3 h-3 rounded-full bg-green-500 hover:bg-green-400 transition-colors cursor-pointer"></div>
            </div>
            <h2 className="font-semibold text-slate-500 text-xs tracking-widest uppercase">Output Console</h2>
          </div>

          <div className="max-h-40 overflow-y-auto shrink-0 p-3">
            {errors.length > 0 && (
              <div className="p-3 bg-red-500/10 border border-red-500/30 rounded-lg">
                <ul className="text-red-400 text-xs font-mono space-y-1.5">
                  {errors.map((err, i) => <li key={i}>❌ {err}</li>)}
                </ul>
              </div>
            )}
          </div>

          <div 
            className="flex-1 p-5 font-mono text-sm text-gray-200 overflow-y-auto leading-relaxed"
            dangerouslySetInnerHTML={{ __html: result || '<span class="text-slate-600">// Waiting for execution...</span>' }}
          />
        </div>

      </div>
    </div>
  );
}

export default App;