(function () {
  const container = document.getElementById('neural-cv-network');
  if (!container || typeof vis === 'undefined') {
    return;
  }

  const rawNodes = (window.NeuralCVData && NeuralCVData.nodes) || [];
  const rawEdges = (window.NeuralCVData && NeuralCVData.edges) || [];

  const nodes = new vis.DataSet(rawNodes.length ? rawNodes : [
    { id: 1, label: 'Letzte Jobstation', group: 'job', shape: 'dot', size: 24 },
    { id: 2, label: 'Projekt: KI-Portal', group: 'project', shape: 'dot', size: 20 },
    { id: 3, label: 'Skill: Datenanalyse', group: 'skill', shape: 'dot', size: 18 },
    { id: 4, label: 'Hobby: Mentoring', group: 'hobby', shape: 'dot', size: 16 }
  ]);

  const edges = new vis.DataSet(rawEdges.length ? rawEdges : [
    { from: 1, to: 2, value: 5 },
    { from: 1, to: 3, value: 4 },
    { from: 2, to: 4, value: 2 }
  ]);

  const network = new vis.Network(container, { nodes, edges }, {
    autoResize: true,
    interaction: { dragNodes: true, hover: true },
    physics: {
      enabled: true,
      barnesHut: { gravitationalConstant: -9000, centralGravity: 0.2, springLength: 120 }
    },
    nodes: {
      font: { color: '#f2f4f8', face: 'Inter', size: 14 },
      borderWidth: 2,
      color: { border: '#2d5be8', background: '#1a1c22', highlight: { border: '#d0d4dc', background: '#1843c8' } }
    },
    edges: {
      color: { color: '#5a6ea8', hover: '#d0d4dc', highlight: '#2d5be8' },
      smooth: { type: 'dynamic' },
      scaling: { min: 1, max: 8 }
    },
    groups: {
      job: { color: { background: '#1b233f', border: '#2d5be8' } },
      project: { color: { background: '#2a2f3f', border: '#7f91cc' } },
      skill: { color: { background: '#1d212b', border: '#b6bcc9' } },
      hobby: { color: { background: '#181a20', border: '#5f6678' } }
    }
  });

  const notice = document.getElementById('network-notice');
  network.on('click', (params) => {
    if (!params.nodes.length) {
      return;
    }

    const node = nodes.get(params.nodes[0]);
    const isPublic = !!node.public;
    const isLoggedIn = !!(window.NeuralCVData && NeuralCVData.isLoggedIn);

    if (!isLoggedIn && !isPublic) {
      notice.innerHTML = '<strong>Zugriff eingeschränkt:</strong> Dieses Neuron ist nur nach Registrierung, Login und SMS-2FA lesbar.';
      return;
    }

    if (node.url) {
      window.location.href = node.url;
      return;
    }

    notice.innerHTML = '<strong>Neuron geöffnet:</strong> Hinterlege für dieses Neuron eine Zielseite im WordPress-Backend.';
  });

  const exportButton = document.getElementById('export-cv');
  if (exportButton) {
    exportButton.addEventListener('click', async () => {
      const response = await fetch('/wp-json/neural-cv/v1/export-pdf', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.wpApiSettings?.nonce || '' }
      });

      if (!response.ok) {
        notice.innerHTML = '<strong>Fehler:</strong> PDF-Export aktuell nicht verfügbar. Prüfe Plugin-Einstellungen.';
        return;
      }

      const data = await response.json();
      if (data.url) {
        window.open(data.url, '_blank', 'noopener');
      }
    });
  }
})();
