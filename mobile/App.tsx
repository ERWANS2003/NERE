import AsyncStorage from '@react-native-async-storage/async-storage';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, SafeAreaView, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';

const API_URL = 'http://127.0.0.1:8000/api';
const TOKEN_KEY = 'nere.sanctum.token';

type Ticket = {
  id: number;
  reference: string;
  titre: string;
  description: string;
  statut?: { nom: string };
  priorite?: { nom: string };
  site?: { nom: string };
};
type NotificationItem = { id: string; data: { titre?: string; message?: string }; read_at: string | null };
type Catalog = { departements: { id: number }[]; sites: { id: number }[]; categories: { id: number }[] };

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const token = await AsyncStorage.getItem(TOKEN_KEY);
  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  });

  const body = await response.json();
  if (!response.ok) throw new Error(body.message ?? body.errors?.email?.[0] ?? 'Une erreur est survenue.');
  return body;
}

export default function App() {
  const [token, setToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    AsyncStorage.getItem(TOKEN_KEY).then((storedToken) => {
      setToken(storedToken);
      setLoading(false);
    });
  }, []);

  if (loading) return <Loading />;
  return <SafeAreaView style={styles.safe}><StatusBar style="light" />{token ? <TicketHome onLogout={() => setToken(null)} /> : <Login onLogin={setToken} />}</SafeAreaView>;
}

function Loading() {
  return <View style={styles.center}><ActivityIndicator color="#c8f169" size="large" /></View>;
}

function Login({ onLogin }: { onLogin: (value: string) => void }) {
  const [email, setEmail] = useState('admin@nere-mining.bf');
  const [password, setPassword] = useState('password');
  const [busy, setBusy] = useState(false);

  async function submit() {
    setBusy(true);
    try {
      const data = await request<{ token: string }>('/auth/login', { method: 'POST', body: JSON.stringify({ email, password, device_name: 'mobile' }) });
      await AsyncStorage.setItem(TOKEN_KEY, data.token);
      onLogin(data.token);
    } catch (error) { Alert.alert('Connexion impossible', (error as Error).message); }
    finally { setBusy(false); }
  }

  return <ScrollView contentContainerStyle={styles.login} keyboardShouldPersistTaps="handled">
    <Text style={styles.eyebrow}>NÉRÉ / MOBILE</Text>
    <Text style={styles.title}>Le support, dans votre poche.</Text>
    <Text style={styles.subtitle}>Connectez-vous pour suivre et créer vos demandes ITSM.</Text>
    <View style={styles.form}>
      <Text style={styles.label}>Adresse email</Text>
      <TextInput autoCapitalize="none" keyboardType="email-address" style={styles.input} value={email} onChangeText={setEmail} />
      <Text style={styles.label}>Mot de passe</Text>
      <TextInput secureTextEntry style={styles.input} value={password} onChangeText={setPassword} />
      <Pressable style={styles.primary} onPress={submit} disabled={busy}><Text style={styles.primaryText}>{busy ? 'Connexion...' : 'Se connecter'}</Text></Pressable>
    </View>
  </ScrollView>;
}

function TicketHome({ onLogout }: { onLogout: () => void }) {
  const [tickets, setTickets] = useState<Ticket[]>([]);
  const [selected, setSelected] = useState<Ticket | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [formOpen, setFormOpen] = useState(false);
  const [notifications, setNotifications] = useState<NotificationItem[]>([]);
  const [catalog, setCatalog] = useState<Catalog | null>(null);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');

  async function load() {
    setRefreshing(true);
    try { const data = await request<{ data: Ticket[] }>('/tickets'); setTickets(data.data); }
    catch (error) { Alert.alert('Chargement impossible', (error as Error).message); }
    finally { setRefreshing(false); }
  }
  useEffect(() => { load(); }, []);
  useEffect(() => { request<{ data: NotificationItem[] }>('/notifications').then((data) => setNotifications(data.data)).catch(() => undefined); }, []);
  useEffect(() => { request<Catalog>('/catalog').then(setCatalog).catch(() => undefined); }, []);

  async function createTicket() {
    try {
      if (!title.trim() || !description.trim() || !catalog?.departements[0] || !catalog.categories[0]) {
        Alert.alert('Informations manquantes', 'Renseignez un titre et une description.');
        return;
      }
      await request('/tickets', {
        method: 'POST', body: JSON.stringify({
          titre: title,
          description,
          type: 'incident',
          departement_id: catalog.departements[0].id,
          ticket_category_id: catalog.categories[0].id,
          site_id: catalog.sites[0]?.id,
          impact: 'Moyen',
          urgence: 'Moyen',
        })
      });
      setTitle('');
      setDescription('');
      setFormOpen(false);
      await load();
      Alert.alert('Ticket créé', 'Votre demande a été envoyée au support.');
    } catch (error) { Alert.alert('Erreur', (error as Error).message); }
  }

  if (selected) return <ScrollView style={styles.screen} contentContainerStyle={styles.content}><Pressable onPress={() => setSelected(null)}><Text style={styles.back}>‹ Tous les tickets</Text></Pressable><Text style={styles.ticketRef}>{selected.reference}</Text><Text style={styles.detailTitle}>{selected.titre}</Text><Text style={styles.description}>{selected.description}</Text><View style={styles.meta}><Text style={styles.badge}>{selected.statut?.nom ?? 'Nouveau'}</Text><Text style={styles.metaText}>{selected.priorite?.nom ?? 'Priorité non définie'} · {selected.site?.nom ?? 'Site non défini'}</Text></View></ScrollView>;

  return <View style={styles.screen}><View style={styles.header}><View><Text style={styles.eyebrow}>NÉRÉ / ITSM</Text><Text style={styles.homeTitle}>Mes tickets</Text></View><Pressable onPress={async () => { await AsyncStorage.removeItem(TOKEN_KEY); onLogout(); }}><Text style={styles.logout}>Quitter</Text></Pressable></View>
    <View style={styles.toolbar}><Text style={styles.count}>{tickets.length} demandes · {notifications.filter((item) => !item.read_at).length} alertes</Text><Pressable onPress={() => setFormOpen(!formOpen)}><Text style={styles.add}>+ Nouveau ticket</Text></Pressable></View>
    {notifications.length > 0 && <View style={styles.notifications}><Text style={styles.notificationTitle}>Dernières alertes</Text>{notifications.slice(0, 3).map((item) => <Text key={item.id} style={styles.notificationText}>{item.data.titre ?? 'Notification'} · {item.data.message ?? ''}</Text>)}</View>}
    {formOpen && <View style={styles.form}><TextInput placeholder="Titre" placeholderTextColor="#718096" style={styles.input} value={title} onChangeText={setTitle} /><TextInput placeholder="Description" placeholderTextColor="#718096" multiline style={[styles.input, styles.multiline]} value={description} onChangeText={setDescription} /><Pressable style={styles.primary} onPress={createTicket}><Text style={styles.primaryText}>Continuer</Text></Pressable></View>}
    <ScrollView contentContainerStyle={styles.content}>{refreshing ? <ActivityIndicator color="#c8f169" /> : tickets.map((ticket) => <Pressable key={ticket.id} style={styles.card} onPress={() => setSelected(ticket)}><Text style={styles.ticketRef}>{ticket.reference}</Text><Text style={styles.cardTitle}>{ticket.titre}</Text><Text style={styles.metaText}>{ticket.statut?.nom ?? 'Nouveau'} · {ticket.priorite?.nom ?? 'Priorité non définie'}</Text></Pressable>)}</ScrollView>
  </View>;
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: '#101a20' },
  screen: { flex: 1, backgroundColor: '#101a20' },
  center: { flex: 1, backgroundColor: '#101a20', alignItems: 'center', justifyContent: 'center' },
  login: { flexGrow: 1, justifyContent: 'center', padding: 28 },
  content: { padding: 20, gap: 12 },
  eyebrow: { color: '#c8f169', fontSize: 12, fontWeight: '800', letterSpacing: 1.5 },
  title: { color: '#f5f7f4', fontSize: 42, fontWeight: '800', lineHeight: 46, marginTop: 16 },
  subtitle: { color: '#a9b7b7', fontSize: 16, lineHeight: 23, marginTop: 14, marginBottom: 30 },
  form: { backgroundColor: '#18262d', borderRadius: 16, padding: 18, gap: 10, marginBottom: 14 },
  label: { color: '#a9b7b7', fontSize: 12, fontWeight: '700' },
  input: { backgroundColor: '#23343b', borderRadius: 10, color: '#f5f7f4', padding: 14, fontSize: 15 },
  multiline: { minHeight: 90, textAlignVertical: 'top' },
  primary: { backgroundColor: '#c8f169', borderRadius: 10, alignItems: 'center', padding: 15, marginTop: 8 },
  primaryText: { color: '#142016', fontSize: 15, fontWeight: '800' },
  header: { padding: 24, paddingTop: 20, flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'space-between' },
  homeTitle: { color: '#f5f7f4', fontSize: 30, fontWeight: '800', marginTop: 5 },
  logout: { color: '#c8f169', fontWeight: '700', paddingBottom: 4 },
  toolbar: { borderTopWidth: 1, borderBottomWidth: 1, borderColor: '#294049', padding: 15, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  count: { color: '#a9b7b7' },
  add: { color: '#c8f169', fontWeight: '800' },
  card: { backgroundColor: '#18262d', borderRadius: 14, padding: 18 },
  ticketRef: { color: '#c8f169', fontSize: 12, fontWeight: '800', letterSpacing: 1 },
  cardTitle: { color: '#f5f7f4', fontSize: 17, fontWeight: '700', marginTop: 8, marginBottom: 12 },
  detailTitle: { color: '#f5f7f4', fontSize: 30, fontWeight: '800', lineHeight: 36, marginTop: 8 },
  description: { color: '#d2dcd9', fontSize: 16, lineHeight: 25, marginTop: 18 },
  back: { color: '#c8f169', fontWeight: '700', marginBottom: 26 },
  meta: { marginTop: 24, gap: 10 },
  badge: { color: '#142016', backgroundColor: '#c8f169', borderRadius: 20, paddingHorizontal: 12, paddingVertical: 7, alignSelf: 'flex-start', fontWeight: '800' },
  metaText: { color: '#9eb0b0', fontSize: 13 },
  notifications: { marginHorizontal: 20, marginTop: 14, padding: 15, borderRadius: 12, backgroundColor: '#21343a' },
  notificationTitle: { color: '#c8f169', fontWeight: '800', marginBottom: 8 },
  notificationText: { color: '#c8d4d0', fontSize: 12, lineHeight: 18, marginTop: 4 },
});
