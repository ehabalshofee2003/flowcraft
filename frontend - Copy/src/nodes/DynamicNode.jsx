import React, { memo } from 'react';
import { Handle, Position } from 'reactflow';

const DynamicNode = ({ data }) => {
  const config = data._nodeConfig;

  if (!config) {
    return (
      <div className="bg-slate-100 border-2 border-dashed border-slate-300 rounded-2xl p-6 min-w-[150px] text-center text-slate-400 text-sm animate-pulse">
        Loading...
      </div>
    );
  }

  const fields = config.schema?.fields || [];
  const inputs = config.ports?.inputs || [];
  const outputs = config.ports?.outputs || [];

  const getHandleStyle = (index, total) => {
    if (total <= 1) return { top: '50%' };
    const offset = ((index + 1) * 100) / (total + 1);
    return { top: `${offset}%` };
  };

  const shouldShowField = (field) => {
    if (!field.showWhen) return true;
    for (const [depField, allowedValues] of Object.entries(field.showWhen)) {
      if (!allowedValues.includes(data[depField])) return false;
    }
    return true;
  };

  // ==========================================
  // نظام الألوان الديناميكي للحالات (States)
  // ==========================================
  const getNodeStyles = () => {
    switch (data.status) {
      case 'running':
        return 'border-amber-300 shadow-[0_0_25px_rgba(251,191,36,0.4)] bg-white';
      case 'success':
        return 'border-emerald-300 shadow-[0_0_15px_rgba(52,211,153,0.2)] bg-emerald-50/50';
      case 'error':
        return 'border-rose-300 shadow-[0_0_15px_rgba(251,113,133,0.2)] bg-rose-50/50';
      default:
        return 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-lg';
    }
  };

  const getHeaderStyles = () => {
    if (data.status === 'running') return 'bg-gradient-to-r from-amber-400 to-orange-500 text-white';
    return 'bg-gradient-to-r from-slate-700 to-slate-900 text-white';
  };

  const renderField = (field) => {
    if (!shouldShowField(field)) return null;

    const commonProps = {
      className: "w-full px-3 py-1.5 border border-slate-200 rounded-lg text-xs bg-slate-50/50 focus:outline-none focus:border-indigo-400 focus:ring-1 focus:ring-indigo-100 transition-colors nodrag",
      value: data[field.name] ?? field.default ?? '',
      onChange: (e) => data.onChange(field.name, e.target.value),
      placeholder: field.placeholder,
    };

    switch (field.type) {
      case 'text':
        return <input type="text" {...commonProps} />;
      case 'number':
        return <input type="number" min={field.min} max={field.max} {...commonProps} />;
      case 'textarea':
        return (
          <textarea 
            {...commonProps} 
            rows={3} 
            style={{ resize: 'none' }}
            className={`${commonProps.className} font-mono`}
          />
        );
      case 'color':
        return (
          <div className="flex items-center gap-2 nodrag">
            <div className="relative w-8 h-8 rounded-lg overflow-hidden border border-slate-200 shadow-inner">
              <input 
                type="color" 
                value={data[field.name] || '#000000'}
                onChange={(e) => data.onChange(field.name, e.target.value)}
                className="absolute inset-0 w-full h-full cursor-pointer border-0 p-0 nodrag"
              />
            </div>
            <span className="text-xs text-slate-500 font-mono">{data[field.name] || '#000000'}</span>
          </div>
        );
       case 'select':
        // معالجة ذكية: الـ Backend أحياناً يرسل Options كـ Object أو كـ Array
        const optionsList = Array.isArray(field.options) 
          ? field.options 
          : Object.entries(field.options || {}).map(([val, lbl]) => ({ value: val, label: lbl }));
        
        return (
          <select {...commonProps}>
            {optionsList.map((opt) => (
              <option key={opt.value ?? opt} value={opt.value ?? opt}>
                {opt.label ?? opt}
              </option>
            ))}
          </select>
        );
        case 'checkbox':
        return (
          <label className="flex items-center gap-2 cursor-pointer nodrag group">
            <div className="w-4 h-4 rounded border-slate-300 flex items-center justify-center transition group-hover:border-indigo-400 bg-white">
              <input 
                type="checkbox" 
                checked={!!data[field.name]}
                onChange={(e) => data.onChange(field.name, e.target.checked)}
                className="opacity-0 absolute w-0 h-0 nodrag"
              />
              {data[field.name] && <span className="text-indigo-600 text-xs">✓</span>}
            </div>
            <span className="text-xs text-slate-600">{field.label}</span>
          </label>
        );
      case 'keyvalue':
        return <KeyValueEditor data={data} fieldName={field.name} />;
      default:
        return null;
    }
  };

  return (
    <div className={`relative border-2 rounded-2xl shadow-md min-w-[220px] max-w-[250px] transition-all duration-300 ${getNodeStyles()}`}>
      
      {/* مؤشر التحميل (يدور أثناء الـ Running) */}
      {data.status === 'running' && (
        <div className="absolute -top-2 -right-2 z-10">
          <div className="w-5 h-5 bg-amber-400 rounded-full animate-spin border-2 border-amber-100 border-t-transparent shadow-lg"></div>
        </div>
      )}

      {/* أيقونة النجاح أو الخطأ */}
      {data.status === 'success' && (
        <div className="absolute -top-2 -right-2 z-10 w-5 h-5 bg-emerald-500 rounded-full flex items-center justify-center text-white text-[10px] shadow-lg">✓</div>
      )}
      {data.status === 'error' && (
        <div className="absolute -top-2 -right-2 z-10 w-5 h-5 bg-rose-500 rounded-full flex items-center justify-center text-white text-[10px] shadow-lg">✕</div>
      )}

      {/* رأس النود (Gradient) */}
      <div className={`${getHeaderStyles()} px-4 py-2 rounded-t-[14px] text-xs font-bold flex items-center justify-between`}>
        <span className="flex items-center gap-2">
          <span className="w-2 h-2 bg-white/30 rounded-full"></span>
          {config.label}
        </span>
      </div>

      {/* جسم النود */}
      <div className="p-3 space-y-2.5">
        {fields.map((field) => (
          <div key={field.name}>
            {field.type !== 'checkbox' && (
              <label className="block text-[10px] font-semibold text-slate-400 mb-1 uppercase tracking-wide">
                {field.label}
              </label>
            )}
            {renderField(field)}
          </div>
        ))}
      </div>

      {/* المداخل (Inputs) - شكل أنيق ومربع */}
      {inputs.map((port, index) => (
        <Handle
          key={`in-${port.id}`}
          type="target"
          position={Position.Left}
          id={port.id}
          style={{ ...getHandleStyle(index, inputs.length), width: 12, height: 12, background: '#6366f1', border: '2px solid white', boxShadow: '0 1px 3px rgba(0,0,0,0.2)' }}
        />
      ))}

      {/* المخرجات (Outputs) */}
      {outputs.map((port, index) => (
        <Handle
          key={`out-${port.id}`}
          type="source"
          position={Position.Right}
          id={port.id}
          style={{ ...getHandleStyle(index, outputs.length), width: 12, height: 12, background: '#10b981', border: '2px solid white', boxShadow: '0 1px 3px rgba(0,0,0,0.2)' }}
        >
          {outputs.length > 1 && (
            <div 
              className="absolute right-5 top-1/2 -translate-y-1/2 text-[10px] font-bold px-1.5 py-0.5 rounded-md nodrag shadow-sm"
              style={{ 
                pointerEvents: 'none',
                color: port.id === 'true' ? '#059669' : '#dc2626',
                backgroundColor: port.id === 'true' ? '#ecfdf5' : '#fef2f2',
                border: `1px solid ${port.id === 'true' ? '#a7f3d0' : '#fecaca'}`
              }}
            >
              {port.label}
            </div>
          )}
        </Handle>
      ))}
    </div>
  );
};

// =========================================================
// مكون Key-Value محسّن
// =========================================================
const KeyValueEditor = ({ data, fieldName }) => {
  const pairs = data[fieldName] || [];

  const handleChange = (index, key, value) => {
    const newPairs = [...pairs];
    newPairs[index] = { ...newPairs[index], [key]: value };
    data.onChange(fieldName, newPairs);
  };

  const addPair = () => {
    data.onChange(fieldName, [...pairs, { key: '', value: '' }]);
  };

  const removePair = (index) => {
    data.onChange(fieldName, pairs.filter((_, i) => i !== index));
  };

  return (
    <div className="space-y-1.5 nodrag">
      {pairs.map((pair, index) => (
        <div key={index} className="flex gap-1.5 items-center">
          <input
            type="text"
            placeholder="Key"
            value={pair.key}
            onChange={(e) => handleChange(index, 'key', e.target.value)}
            className="w-1/2 px-2 py-1 border border-slate-200 rounded-md text-[10px] focus:outline-none focus:border-indigo-400 bg-slate-50/50 nodrag"
          />
          <input
            type="text"
            placeholder="Value"
            value={pair.value}
            onChange={(e) => handleChange(index, 'value', e.target.value)}
            className="w-1/2 px-2 py-1 border border-slate-200 rounded-md text-[10px] focus:outline-none focus:border-indigo-400 bg-slate-50/50 nodrag"
          />
          <button onClick={() => removePair(index)} className="text-slate-300 hover:text-rose-500 transition text-xs nodrag">✕</button>
        </div>
      ))}
      <button 
        onClick={addPair} 
        className="text-indigo-500 hover:text-indigo-700 text-[10px] font-semibold nodrag flex items-center gap-1"
      >
        <span className="text-sm leading-none">+</span> Add Item
      </button>
    </div>
  );
};

export default memo(DynamicNode);